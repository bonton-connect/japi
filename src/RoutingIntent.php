<?php

namespace Bonton\Japi;

class RoutingIntent {
    public static function makeFrom($method, $base, $request_segments) {
        $method = strtolower($method);

        $base_segment_count = count(array_filter(explode('/', $base), function ($seq) {
            return trim($seq) !== '';
        }));

        $segments = array_slice($request_segments, $base_segment_count);
        $segments_len = count($segments);

        $action = null;

        if ($segments_len === 1 && $method === 'get') {

            $action = GET_RESOURCES;
        } else if ($segments_len === 2 && $method === 'get') {

            $action = GET_RESOURCE;
        } else if ($segments_len === 4 && $method === 'get' && $segments[2] === 'relationships') {

            $action = GET_RESOURCE_RELATIONSHIP;
        } else if ($segments_len === 3 && $method === 'get' && $segments[2] !== 'relationships') {

            $action = GET_RELATED_RESOURCE;
        } else if ($segments_len === 1 && $method === 'post') {

            $action = CREATE_RESOURCE;
        } else if ($segments_len === 4 && $method === 'post' && $segments[2] === 'relationships') {

            $action = CREATE_RESOURCE_RELATIONSHIP;
        } else if ($segments_len === 2 && $method === 'patch') {

            $action = UPDATE_RESOURCE;
        } else if ($segments_len === 4 && $method === 'patch' && $segments[2] === 'relationships') {

            $action = UPDATE_RESOURCE_RELATIONSHIP;
        } else if ($segments_len === 2 && $method === 'delete') {

            $action = DELETE_RESOURCE;
        } else if ($segments_len === 4 && $method === 'delete' && $segments[2] === 'relationships') {

            $action = DELETE_RESOURCE_RELATIONSHIP;
        } else {

            return abort(400);
        }

        $resource_type = $segments[0];
        $relation_name = null;
        $resource_identifier = null;

        if ($segments_len === 3) {
            $relation_name = $segments[2];
        } else if ($segments_len === 4) {
            $relation_name = $segments[3];
        }

        if ($segments_len > 1) {
            $resource_identifier = $segments[1];
        }

        return new static($action, $resource_type, $resource_identifier, $relation_name);
    }

    public $action = null;
    public $resource_type = null;
    public $resource_identifier = null;
    public $relation_name = null;

    public function __construct($action, $resource_type, $resource_identifier = null, $relation_name = null)
    {
        $this->action = $action;
        $this->resource_type = $resource_type;
        $this->resource_identifier = $resource_identifier;
        $this->relation_name = $relation_name;
    }
}