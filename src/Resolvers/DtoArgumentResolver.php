<?php
declare(strict_types=1);

namespace NiceYu\ThinkDto\Resolvers;

use DateTime;
use NiceYu\ThinkDto\Annotations\Validator;
use NiceYu\ThinkDto\Annotations\ValidatorGroup;
use NiceYu\ThinkDto\Contracts\DtoInterface;
use NiceYu\ThinkDto\Exceptions\ValidateException;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use think\route\RuleItem;
use think\Validate;

class DtoArgumentResolver
{
    /**
     * @param DtoInterface $object
     * @param RuleItem $ruleItem
     * @return DtoInterface
     */
    public function resolve(DtoInterface $object, RuleItem $ruleItem): DtoInterface
    {
        $route = $ruleItem->getRoute();
        /** @noinspection PhpStrFunctionsInspection */
        if (strpos($route, '@') === false) {
            return $object;
        }

        /** 解析控制器和方法 */
        [$controllerClass, $action] = explode('@', $route, 2);
        if (!class_exists($controllerClass) || !method_exists($controllerClass, $action)) {
            return $object;
        }
        try {
            /** 获取到控制器方法中使用的验证组 */
            $groups = ['default'];
            $method = new ReflectionMethod($controllerClass, $action);

            /** @noinspection PhpUndefinedMethodInspection */
            foreach ($method->getAttributes(ValidatorGroup::class) as $attribute) {
                $groups = $attribute->newInstance()->groups;
                break;
            }

            /** 反射 dto */
            $reflection = new ReflectionClass($object);
            $data = $ruleItem->getVars();

            /** 验证和赋值流程 */
            $validate = new Validate();
            foreach ($reflection->getProperties() as $property) {
                $name = $property->getName();
                $value = $data[$name] ?? null;

                /** 类型转换（保持内联） */
                if ($property->hasType() && $value !== null) {
                    $object->{$name} = $this->castValueByType(
                        $property->getType()->getName(),
                        $value
                    );
                }

                /**
                 * 验证规则（保持内联）
                 * @noinspection PhpUndefinedMethodInspection
                 */
                foreach ($property->getAttributes(Validator::class) as $attr) {
                    $validator = $attr->newInstance();
                    if (array_intersect($groups, $validator->groups)) {
                        $ruleName = strtok($validator->rule, ':');
                        $validate->message(["$name.$ruleName" => $validator->message])->rule($name, $validator->rule);
                    }
                }
            }

            if (!$validate->check($data)) {
                throw new ValidateException(message: $validate->getError(), code: 422);
            }

            return $object;
        } catch (ReflectionException) {
            return $object;
        }
    }

    /**
     * 仅拆分真正可复用的类型转换逻辑
     * @param string $type
     * @param $value
     * @return array|DateTime|false|float|int|mixed|string
     */
    private function castValueByType(string $type, $value): mixed
    {
        return match ($type) {
            'int' => (int)$value,
            'float' => (float)$value,
            'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'string' => (string)$value,
            'array' => (array)$value,
            'DateTime' => DateTime::createFromFormat('Y-m-d H:i:s', $value)
                ?: DateTime::createFromFormat('Y-m-d', $value),
            default => $value,
        };
    }
}