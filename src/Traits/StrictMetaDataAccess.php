<?php

namespace Solcloud\Consumer\Traits;

trait StrictMetaDataAccess
{

    protected function getMemberValueHelper(string $rootNode, string ...$memberName): mixed
    {
        $val = $this->{$rootNode};
        foreach ($memberName as $m) {
            $val = $val->{$m};
        }
        return $val;
    }

    public function getDataMemberString(string ...$member): string
    {
        return $this->getMemberValueHelper('data', ...$member);
    }

    public function getDataMemberInt(string ...$member): int
    {
        return $this->getMemberValueHelper('data', ...$member);
    }

    public function getDataMemberBool(string ...$member): bool
    {
        return $this->getMemberValueHelper('data', ...$member);
    }

    public function getDataMemberFloat(string ...$member): float
    {
        return $this->getMemberValueHelper('data', ...$member);
    }

    public function getDataMemberArray(string ...$member): array
    {
        return $this->getMemberValueHelper('data', ...$member);
    }

    public function getDataMemberObject(string ...$member): object
    {
        return $this->getMemberValueHelper('data', ...$member);
    }

    public function getMetaMemberString(string ...$member): string
    {
        return $this->getMemberValueHelper('meta', ...$member);
    }

    public function getMetaMemberInt(string ...$member): int
    {
        return $this->getMemberValueHelper('meta', ...$member);
    }

    public function getMetaMemberBool(string ...$member): bool
    {
        return $this->getMemberValueHelper('meta', ...$member);
    }

    public function getMetaMemberFloat(string ...$member): float
    {
        return $this->getMemberValueHelper('meta', ...$member);
    }

    public function getMetaMemberArray(string ...$member): array
    {
        return $this->getMemberValueHelper('meta', ...$member);
    }

    public function getMetaMemberObject(string ...$member): object
    {
        return $this->getMemberValueHelper('meta', ...$member);
    }

}
