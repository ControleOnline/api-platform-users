<?php

namespace ControleOnline\Users\Tests\Entity;

use PHPUnit\Framework\TestCase;

class UserSerializationGroupsTest extends TestCase
{
    public function testUserSecretsAreNotSerializedThroughPeopleRead(): void
    {
        $source = file_get_contents(__DIR__ . '/../../src/Entity/User.php');

        self::assertIsString($source);
        self::assertDoesNotMatchRegularExpression('/#\[Groups\(\[(?:(?!\]\)\]).)*people:read(?:(?!\]\)\]).)*\]\)\]\s*private \?int \$id = null;/s', $source);
        self::assertDoesNotMatchRegularExpression('/#\[Groups\(\[(?:(?!\]\)\]).)*people:read(?:(?!\]\)\]).)*\]\)\]\s*#\[Assert\\NotBlank.*?private string \$username = \'\';/s', $source);
        self::assertDoesNotMatchRegularExpression('/#\[Groups\(\[(?:(?!\]\)\]).)*people:read(?:(?!\]\)\]).)*\]\)\]\s*private string \$apiKey = \'\';/s', $source);
    }
}
