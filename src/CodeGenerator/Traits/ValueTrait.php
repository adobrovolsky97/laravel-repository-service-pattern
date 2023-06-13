<?php

namespace Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Traits;

/**
 * Value Trait
 */
trait ValueTrait
{
    /**
     * Render value by type
     *
     * @param mixed $value
     * @return string|null
     */
    protected function renderTyped($value): ?string
    {
        $type = gettype($value);

        switch ($type) {
            case 'boolean':
                $value = $value ? 'true' : 'false';
                break;
            case 'NULL':
                $value = 'null';
                break;
            case 'int':
                break;
            case 'string':
                if (!preg_match('/::/', $value)) {
                    $value = in_array($value, ['false', 'true'])
                        ? $value
                        : sprintf('\'%s\'', addslashes($value));
                }
                break;
            case 'array':
                $parts = [];
                foreach ($value as $item) {
                    $parts[] = $this->renderTyped($item);
                }

                $value = count($parts) > 5
                    ? $this->printBigArray($parts)
                    : '[' . implode(', ', $parts) . ']';
                break;
            default:
                $value = null;
        }

        return $value;
    }

    /**
     * Print big array
     *
     * @param array $values
     * @return string
     */
    private function printBigArray(array $values): string
    {
        $lines = ['['];

        $count = count($values);

        foreach ($values as $id => $value) {
            $lines[] = "\t\t" . $value . ($id === $count - 1 ? '' : ',');
        }

        $lines[] = "\t]";

        return implode(PHP_EOL, $lines);
    }
}
