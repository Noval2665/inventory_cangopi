<?php

namespace App\Helpers;

class CASL
{
    static function can(array $permissions, string $action, string $subject = 'all', bool $checkAdmin = true)
    {
        $filtered = array_filter($permissions, function ($permission) use ($action, $subject, $checkAdmin) {
            return (
                $permission['action'] === $action ||
                (
                    $checkAdmin && $permission['action'] === 'manage'
                )
            )
                &&
                (
                    $permission['subject'] === $subject ||
                    (
                        $checkAdmin && $permission['subject'] === 'all'
                    )
                );
        });

        return count($filtered) > 0;
    }
}
