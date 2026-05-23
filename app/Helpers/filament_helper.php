<?php

use Filament\Notifications\Notification;

if (!function_exists('notification')) {
    function notification(string $message, $type = 'success'): Notification
    {
        return Notification::make()
            ->title($message)
            ->{$type}()
            ->send();
    }
}
