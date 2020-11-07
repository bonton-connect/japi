<?php

namespace Bonton\Japi;

interface Resolver
{
    public function index();
    public function get(string $id);
    public function create(Payload $data);
    public function update(Payload $data);
    public function delete(string $id);
}
