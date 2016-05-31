<?php

namespace Staticus\Resources\Commands;

use Staticus\Resources\ResourceDOInterface;

/**
 * @deprecated
 * @see \Staticus\Resources\Commands\FindResourceOptionsCommand
 */
trait ShellFindCommandTrait
{
    /**
     * @param $baseDir
     * @param $namespace
     * @param $uuid
     * @param $type
     * @param string $variant
     * @param int $version
     * @return string
     */
    protected function getShellFindCommand($baseDir, $namespace, $uuid, $type, $variant = ResourceDOInterface::DEFAULT_VARIANT, $version = ResourceDOInterface::DEFAULT_VERSION)
    {
        if ($namespace) {
            $namespace .= DIRECTORY_SEPARATOR;
        }
        $command = 'find ';
        if ($version !== ResourceDOInterface::DEFAULT_VERSION) {
            $command .= $baseDir . $namespace . $type . DIRECTORY_SEPARATOR . $variant . DIRECTORY_SEPARATOR . $version . DIRECTORY_SEPARATOR;
        } elseif ($variant !== ResourceDOInterface::DEFAULT_VARIANT) {
            $command .= $baseDir . $namespace . $type . DIRECTORY_SEPARATOR . $variant . DIRECTORY_SEPARATOR;
        } else {
            $command .= $baseDir . $namespace . $type . DIRECTORY_SEPARATOR;
        }

        $command .= ' -type f -name ' . $uuid . '.' . $type;

        return $command;
    }

    /**
     * @param $baseDir
     * @param $namespace
     * @param $uuid
     * @param $type
     * @param $variant
     * @return int
     */
    protected function findLastExistsVersion($baseDir, $namespace, $uuid, $type, $variant)
    {
        $variantVersions = $this->findAllVersions($baseDir, $namespace, $uuid, $type, $variant);
        $lastVersion = (int)current($variantVersions);

        return $lastVersion;
    }

    protected function findAllVersions($baseDir, $namespace, $uuid, $type, $variant)
    {
        $command = $this->getShellFindCommand($baseDir, $namespace, $uuid, $type, $variant);
        $result = shell_exec($command);
        $result = array_filter(explode(PHP_EOL, $result));
        if ($namespace) {
            $namespace .= DIRECTORY_SEPARATOR;
        }
        $prefixPath = $baseDir . $namespace . $type . DIRECTORY_SEPARATOR . $variant . DIRECTORY_SEPARATOR;
        $prefixPathLength = mb_strlen($prefixPath, 'UTF-8');
        $variantVersions = [];
        // Определяем последнюю версию
        foreach ($result as $path) {
            $path = str_replace('//', '/', $path);
            // Проверяем, что из shell не прилетело чего-нибудь лишнего, не содержащего нужные нам маршруты
            if (0 === strpos($path, $prefixPath)) {
                $suffix = substr($path, $prefixPathLength);
                $nextSeparator = strpos($suffix, DIRECTORY_SEPARATOR);
                if ($nextSeparator) {
                    $variantVersions[] = substr($suffix, 0, $nextSeparator);
                }
            }
        }
        $variantVersions = array_unique($variantVersions);
        rsort($variantVersions, SORT_NUMERIC);

        return $variantVersions;
    }
}