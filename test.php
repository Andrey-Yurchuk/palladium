<?php

require_once 'autoload.php';

use App\Service\UserManager;

$userManager = new UserManager();

try {
    echo "=== 1. Добавление пользователей ===\n";
    $user1Id = $userManager->addUser("Андрей Андреев", "andrey88@gmail.com");
    echo "Добавлен пользователь Андрей Андреев c ID: $user1Id\n";

    $user2Id = $userManager->addUser("Расмус Лердорф", "rasmus-lerdorf@yahoo.com");
    echo "Добавлен пользователь Расмус Лердорф c ID: $user2Id\n";


    echo "\n=== 2. Список пользователей ===\n";
    $users = $userManager->getUsers();
    foreach ($users as $user) {
        echo sprintf(
            "ID: %d | Имя: %s | Email: %s | Создан: %s\n",
            $user['id'],
            $user['name'],
            $user['email'],
            $user['created_at']
        );
    }


    echo "\n=== 3. Обновление пользователя c ID $user1Id ===\n";
    $userManager->updateUser($user1Id, "Яков Федоров", "yakov.fedorov@gmail.com");
    echo "Данные пользователя c ID $user1Id обновлены\n";

    $updatedUser = $userManager->getUsers()[0];
    echo "Обновленные данные: \n";
    echo sprintf(
        "ID: %d | Имя: %s | Email: %s | Обновлен: %s\n",
        $updatedUser['id'],
        $updatedUser['name'],
        $updatedUser['email'],
        $updatedUser['updated_at']
    );


    echo "\n=== 4. Удаление пользователя c ID $user2Id ===\n";
    $userManager->deleteUser($user2Id);
    echo "Пользователь c ID $user2Id удален\n";
} catch (RuntimeException | InvalidArgumentException $e) {
    die("Ошибка: " . $e->getMessage());
}

/*** Результат выполнения:
 *
 * === 1. Добавление пользователей ===
 * Добавлен пользователь Андрей Андреев c ID: 1
 * Добавлен пользователь Расмус Лердорф c ID: 2
 *
 * === 2. Список пользователей ===
 * ID: 1 | Имя: Андрей Андреев | Email: andrey88@gmail.com | Создан: 2025-02-06 22:52:27
 * ID: 2 | Имя: Расмус Лердорф | Email: rasmus-lerdorf@yahoo.com | Создан: 2025-02-06 22:52:27
 *
 * === 3. Обновление пользователя c ID 1 ===
 * Данные пользователя c ID 1 обновлены
 * Обновленные данные:
 * ID: 1 | Имя: Яков Федоров | Email: yakov.fedorov@gmail.com | Обновлен: 2025-02-06 22:52:27
 *
 * === 4. Удаление пользователя c ID 2 ===
 * Пользователь c ID 2 удален
 */