<?php

declare(strict_types=1);

namespace PhpCollective;

use PHPUnit\Framework\MockObject\MockObject;
use RuntimeException;

class MockExampleTest
{
    /**
     * @return Service|\PHPUnit\Framework\MockObject\MockObject
     */
    public function missingMockReturnType()
    {
        return $this->createMock(Service::class);
    }

    /**
     * @return Service|\PHPUnit\Framework\MockObject\MockObject
     */
    public function invalidTypehint()
    {
        return $this->createMock(Service::class);
    }

    /**
     * @return Service|\PHPUnit\Framework\MockObject\MockObject
     */
    public function missingTypeHint(): Service
    {
        return $this->createMock(Service::class);
    }

    /**
     * @return Service|Repository|\PHPUnit\Framework\MockObject\MockObject
     */
    public function complexMockMustNotHaveTypeHint(): Service
    {
        return $this->createMock(Service::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    public function missingMockedClass()
    {
        return $this->createMock(Service::class);
    }

    /**
     * @return Service|\PHPUnit\Framework\MockObject\MockObject
     */
    public function validMock(): Service
    {
        return $this->createMock(Service::class);
    }

    /**
     * @return RuntimeException
     */
    public function nonMockReturnType(): RuntimeException
    {
        return new RuntimeException('valid');
    }
}

class Service
{
}

class Repository
{
}
