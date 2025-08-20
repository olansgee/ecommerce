<?php

namespace App\Core;

class Error {
    public static function errorHandler($level, $message, $file, $line) {
        if (error_reporting() !== 0) {
            throw new \ErrorException($message, 0, $level, $file, $line);
        }
    }

    public static function exceptionHandler($exception) {
        $code = $exception->getCode();
        if ($code != 404) {
            $code = 500;
        }
        http_response_code($code);

        $log = dirname(__DIR__) . '/logs/' . date('Y-m-d') . '.txt';
        ini_set('error_log', $log);

        $message = "Uncaught exception: '" . get_class($exception) . "'";
        $message .= " with message '" . $exception->getMessage() . "'";
        $message .= "\nStack trace: " . $exception->getTraceAsString();
        $message .= "\nThrown in '" . $exception->getFile() . "' on line " . $exception->getLine();

        error_log($message);

        echo "<h1>Error</h1>";
        echo "<p>An error occurred. Please try again later.</p>";
        // In a real application, you would show a user-friendly error page.
        // For development, you might want to display the full error message.
        // echo "<p><b>Message:</b> " . $exception->getMessage() . "</p>";
        // echo "<p><b>Stack trace:</b><pre>" . $exception->getTraceAsString() . "</pre></p>";
        // echo "<p><b>Thrown in:</b> " . $exception->getFile() . " on line " . $exception->getLine() . "</p>";
    }
}
