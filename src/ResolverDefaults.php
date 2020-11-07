<?php

namespace Bonton\Japi;

use Bonton\Japi\Exceptions\OperationNotAvailable;

trait ResolverDefaults
{
    public function index()
    {
        throw new OperationNotAvailable("listing");
    }

    public function get(string $id)
    {
        throw new OperationNotAvailable("get");
    }

    public function create(Payload $data)
    {
        throw new OperationNotAvailable("create");
    }

    public function update(Payload $data)
    {
        throw new OperationNotAvailable("update");
    }

    public function delete(string $id)
    {
        throw new OperationNotAvailable("delete");
    }
}
