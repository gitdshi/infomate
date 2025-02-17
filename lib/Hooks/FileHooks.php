<?php

namespace OCA\InfoMate\Hooks;

use OCP\Files\Node;
use OCP\Files\Node\File;
use OCP\IUserSession;
use OCP\Util;

class FileHooks {
    private $userSession;

    public function __construct(IUserSession $userSession) {
        $this->userSession = $userSession;
    }

    public function fileUpload($node) {
        $currentUser = $this->userSession->getUser();
        $currentUserId = $currentUser ? $currentUser->getUID() : 'unknown';

        if (is_array($node)) {
            foreach ($node as $key => $value) {
                error_log("Key: $key, Value: $value"); // Print each key-value pair to console
                if ($key === 'path') {
                    // TODO: move those param link address to configuration file later
                    $url = 'http://host.docker.internal:8000/nextcloud/files/sync';
                    $data = json_encode(['user' => $currentUserId, 'path' => $value]);

                    $ch = curl_init($url);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, [
                        'Content-Type: application/json',
                        'Content-Length: ' . strlen($data)
                    ]);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

                    $response = curl_exec($ch);
                    if (curl_errno($ch)) {
                        error_log('cURL error: ' . curl_error($ch));
                    } else {
                        error_log("Response from sync: $response");
                    }
                    curl_close($ch);
                }
            }
        } elseif ($node instanceof File) {
            // Your custom logic here
            error_log('File uploaded: ' . $node->getPath()); // Print to console
        } else {
            error_log('Unexpected type for file upload hook: ' . gettype($node)); // Print to console
        }
    }
}