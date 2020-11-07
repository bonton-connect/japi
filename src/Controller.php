<?php

namespace Bonton\Japi;

use Bonton\Japi\Services\Main;
use Exception;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;

class Controller extends BaseController
{
    protected $main;

    public function __construct(Main $main)
    {
        $this->main = $main;
    }

    public function handle(Request $req)
    {
        $intent = RoutingIntent::makeFrom(
            $req->method(),
            $this->main->getBasePrefix(),
            $req->segments()
        );

        try {
            if ($intent->action === GET_RESOURCES) {
                $abstract = $this->main->retreiver->index(
                    $req,
                    $intent->resource_type
                );
            } else if ($intent->action === GET_RESOURCE) {
                $abstract = $this->main->retreiver->single(
                    $req,
                    $intent->resource_type,
                    $intent->resource_identifier
                );
            } else if ($intent->action === CREATE_RESOURCE) {
                $abstract = $this->main->preper->create(
                    $req,
                    $intent->resource_type
                );
            } else if ($intent->action === UPDATE_RESOURCE) {
                $abstract = $this->main->preper->update(
                    $req,
                    $intent->resource_type,
                    $intent->resource_identifier
                );
            } else if ($intent->action === DELETE_RESOURCE) {
                $abstract = $this->main->destroyer->delete(
                    $req,
                    $intent->resource_type,
                    $intent->resource_identifier
                );
            } else if ($intent->action === GET_RELATED_RESOURCE) {
                $abstract = $this->main->relationshipRetreiver->indexResources(
                    $req,
                    $intent->resource_type,
                    $intent->resource_identifier,
                    $intent->relation_name
                );
            } else if ($intent->action === GET_RESOURCE_RELATIONSHIP) {
                $abstract = $this->main->relationshipRetreiver->index(
                    $req,
                    $intent->resource_type,
                    $intent->resource_identifier,
                    $intent->relation_name
                );
            } else if ($intent->action === CREATE_RESOURCE_RELATIONSHIP) {
                $abstract = $this->main->relationshipPreper->create(
                    $req,
                    $intent->resource_type,
                    $intent->resource_identifier,
                    $intent->relation_name
                );
            } else if ($intent->action === UPDATE_RESOURCE_RELATIONSHIP) {
                $abstract = $this->main->relationshipPreper->update(
                    $req,
                    $intent->resource_type,
                    $intent->resource_identifier,
                    $intent->relation_name
                );
            } else if ($intent->action === DELETE_RESOURCE_RELATIONSHIP) {
                $abstract = $this->main->relationshipDestroyer->delete(
                    $req,
                    $intent->resource_type,
                    $intent->resource_identifier,
                    $intent->relation_name
                );
            }

            return $this->main->respond($abstract);
        } catch (Exception $exception) {
            return $this->main->switchErrors($exception);
        }
    }
}
