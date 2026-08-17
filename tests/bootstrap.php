<?php

$autoloadPaths = [
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/../../../../vendor/autoload.php',
];

foreach ($autoloadPaths as $autoloadPath) {
    if (is_file($autoloadPath)) {
        require_once $autoloadPath;
        break;
    }
}

if (!interface_exists('Symfony\\Component\\HttpKernel\\Exception\\HttpExceptionInterface')) {
    eval('namespace Symfony\\Component\\HttpKernel\\Exception; interface HttpExceptionInterface extends \\Throwable { public function getStatusCode(): int; }');
}

if (!class_exists('Symfony\\Component\\HttpKernel\\Exception\\BadRequestHttpException')) {
    eval('namespace Symfony\\Component\\HttpKernel\\Exception; class BadRequestHttpException extends \\RuntimeException implements HttpExceptionInterface { public function __construct(string $message = "", int $code = 0, ?\\Throwable $previous = null) { parent::__construct($message, $code, $previous); } public function getStatusCode(): int { return 400; } }');
}

if (!class_exists('Symfony\\Component\\HttpFoundation\\Request')) {
    eval('namespace Symfony\\Component\\HttpFoundation; class Request { public function __construct(private ?string $content = null) {} public function getContent(): string { return $this->content ?? ""; } }');
}

if (!class_exists('Symfony\\Component\\HttpFoundation\\JsonResponse')) {
    eval('namespace Symfony\\Component\\HttpFoundation; class JsonResponse { public function __construct(private mixed $data = null, private int $status = 200) {} public function getStatusCode(): int { return $this->status; } public function getContent(): string { return json_encode($this->data, JSON_THROW_ON_ERROR); } }');
}

if (!interface_exists('Symfony\\Component\\Security\\Core\\User\\UserInterface')) {
    eval('namespace Symfony\\Component\\Security\\Core\\User; interface UserInterface { public function getRoles(): array; public function eraseCredentials(): void; public function getUserIdentifier(): string; }');
}

if (!interface_exists('Symfony\\Component\\Security\\Core\\User\\PasswordAuthenticatedUserInterface')) {
    eval('namespace Symfony\\Component\\Security\\Core\\User; interface PasswordAuthenticatedUserInterface { public function getPassword(): ?string; }');
}

if (!interface_exists('Symfony\\Component\\PasswordHasher\\Hasher\\UserPasswordHasherInterface')) {
    eval('namespace Symfony\\Component\\PasswordHasher\\Hasher; interface UserPasswordHasherInterface { public function hashPassword(object $user, string $plainPassword): string; }');
}

if (!interface_exists('Doctrine\\ORM\\EntityManagerInterface')) {
    eval('namespace Doctrine\\ORM; interface EntityManagerInterface { public function getRepository(string $className); public function persist(object $object): void; public function flush(): void; public function getConnection(); }');
}

if (!class_exists('ControleOnline\\Service\\FileService')) {
    eval('namespace ControleOnline\\Service; class FileService { public function getFileUrl(object $entity): string { return ""; } }');
}

if (!class_exists('ControleOnline\\Service\\PeopleRoleService')) {
    eval('namespace ControleOnline\\Service; class PeopleRoleService { public function getGrantedRoles(object $people): array { return []; } }');
}

if (!class_exists('ControleOnline\\Entity\\People', false)) {
    eval('namespace ControleOnline\\Entity; class People {}');
}
