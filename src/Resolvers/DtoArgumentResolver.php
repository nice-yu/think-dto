<?php
declare(strict_types=1);

namespace NiceYu\ThinkDto\Resolvers;

use app\Request;
use DateTime;
use DateTimeZone;
use Exception;
use NiceYu\ThinkDto\Annotations\Validator;
use NiceYu\ThinkDto\Annotations\ValidatorGroup;
use NiceYu\ThinkDto\Annotations\ValidatorIgnore;
use NiceYu\ThinkDto\Contracts\DtoInterface;
use NiceYu\ThinkDto\Exceptions\ValidateException;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use think\Validate;

class DtoArgumentResolver
{
    private function castValueByType(string $type, $value): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'int' => is_numeric($value) ? (int)$value : 0,
            'float' => is_numeric($value) ? (float)$value : 0.0,
            'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'string' => (string)$value,
            'array' => is_array($value) ? $value : (empty($value) ? [] : (array)$value),
            'DateTime' => $this->dateTimeParse((string)$value),
            default => $value,
        };
    }

    private function dateTimeParse(string $dateTimeStr): DateTime
    {
        try {
            /** 处理纯时间 (HH:MM 或 HH:MM:SS) */
            if (preg_match('/^\d{1,2}:\d{2}(?::\d{2})?$/', $dateTimeStr)) {
                $dateTimeStr = date('Y-m-d') . ' ' . $dateTimeStr;
            }

            /** 补全不规范的日期格式 (如 2021-1-12 → 2021-01-12) */
            if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})(?: (\d{2}:\d{2}(?::\d{2})?))?$/', $dateTimeStr, $matches)) {
                $dateTimeStr = sprintf(
                    '%04d-%02d-%02d%s',
                    $matches[1], // 年
                    $matches[2], // 月
                    $matches[3], // 日
                    $matches[4] ?? '' // 时间部分
                );
                return new DateTime($dateTimeStr, new DateTimeZone(env('TIME_ZONE', 'Asia/Shanghai')));
            }

            /** 严格格式验证 */
            if (!preg_match('/^\d{4}-\d{2}-\d{2}(?: \d{2}:\d{2}(?::\d{2})?)?$/', $dateTimeStr)) {
                throw new ValidateException('日期时间格式必须为 YYYY-MM-DD [HH:MM[:SS]]');
            }

            return new DateTime($dateTimeStr, new DateTimeZone(env('TIME_ZONE', 'Asia/Shanghai')));
        } catch (Exception) {
            throw new ValidateException('无效的日期时间值');
        }
    }

    public function resolve(DtoInterface $object, Request $request): DtoInterface
    {
        try {
            $reflection = new ReflectionClass($object);
            $inputData = $request->param();

            /** 属性赋值 */
            foreach ($inputData as $key => $value) {
                if (property_exists($object, $key)) {
                    $property = $reflection->getProperty($key);
                    $type = $property->getType();
                    $object->{$key} = $this->castValueByType(
                        $type ? $type->getName() : 'mixed',
                        $value
                    );
                }
            }

            /** 验证跳过逻辑 */
            if ($this->shouldSkipValidation($reflection, $request)) {
                return $object;
            }

            /** 执行验证 */
            $this->validateObject($reflection, $request, $inputData);

            return $object;
        } catch (ReflectionException) {
            return $object;
        }
    }

    private function shouldSkipValidation(ReflectionClass $reflection, Request $request): bool
    {
        /**
         * 类级别跳过
         * @noinspection PhpUndefinedMethodInspection
         */
        if (count($reflection->getAttributes(ValidatorIgnore::class)) > 0) {
            return true;
        }

        /** 无效路由跳过 */
        $route = $request->rule()->getRoute();
        /** @noinspection PhpUndefinedFunctionInspection */
        if (!str_contains($route, '@')) {
            return true;
        }

        /** 无效方法跳过 */
        [$controllerClass, $action] = explode('@', $route, 2);
        return !class_exists($controllerClass) || !method_exists($controllerClass, $action);
    }

    private function validateObject(ReflectionClass $reflection, Request $request, array $inputData): void {
        $groups = ['default'];
        $route = $request->rule()->getRoute();
        [$controllerClass, $action] = explode('@', $route, 2);

        if (class_exists($controllerClass) && method_exists($controllerClass, $action)) {
            $method = new ReflectionMethod($controllerClass, $action);
            /** @noinspection PhpUndefinedMethodInspection */
            foreach ($method->getAttributes(ValidatorGroup::class) as $attribute) {
                $groups = $attribute->newInstance()->groups;
                break;
            }
        }

        $validate = new Validate();
        foreach ($reflection->getProperties() as $property) {
            $name = $property->getName();

            /**
             * 检查忽略
             * @noinspection PhpUndefinedMethodInspection
             */
            foreach ($property->getAttributes(ValidatorIgnore::class) as $attr) {
                $ignore = $attr->newInstance();
                if (empty($ignore->groups) || array_intersect($groups, $ignore->groups)) {
                    continue 2;
                }
            }

            /**
             * 添加规则
             * @noinspection PhpUndefinedMethodInspection
             */
            foreach ($property->getAttributes(Validator::class) as $attr) {
                $validator = $attr->newInstance();
                if (array_intersect($groups, $validator->groups)) {
                    $ruleName = strtok($validator->rule, ':');
                    $validate->message(["$name.$ruleName" => $validator->message])
                        ->rule($name, $validator->rule);
                }
            }
        }

        if (!$validate->check($inputData)) {
            throw new ValidateException($validate->getError(), 422);
        }
    }
}
