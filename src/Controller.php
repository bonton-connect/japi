<?php
namespace BontonConnect\Japi;

use Exception;
use Generator;
use Bonton\Japi\Errors\OrphanRelationLink;
use Bonton\Japi\Errors\OrphanRelationMeta;
use Bonton\Japi\Errors\RelatedResourceNotFound;
use Bonton\Japi\Errors\ResourceNotFound;
use Bonton\Japi\Relationships\HasMany;
use Bonton\Japi\Yieldables\Attribute;
use Bonton\Japi\Yieldables\Link;
use Bonton\Japi\Yieldables\Meta;
use Bonton\Japi\Yieldables\Record;
use Bonton\Japi\Yieldables\Relationship;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

const GET_RESOURCES = 1;
const GET_RESOURCE = 2;
const GET_RESOURCE_RELATIONSHIP = 3;
const GET_RELATED_RESOURCE = 4;

const CREATE_RESOURCE = 20;
const CREATE_RESOURCE_RELATIONSHIP = 21;

const UPDATE_RESOURCE = 60;
const UPDATE_RESOURCE_RELATIONSHIP = 61;

const DELETE_RESOURCE = 80;
const DELETE_RESOURCE_RELATIONSHIP = 81;

abstract class Controller extends BaseController
{
    public $resources = null;
    protected $controllers = [];
    protected $primary_relation = false;
    protected $page = 1;
    protected $container = null;

    abstract protected function base(): string;
    abstract protected function resources(): array;

    public function __construct()
    {
        $this->resources = $this->resources();
    }

    protected function getResourceController($res): ResourceController
    {
        if (!isset($this->resources[$res])) {
            throw new Exception("$res is not a recognized resource.", 1);
        }

        if (!isset($this->controllers[$res])) {
            $this->controllers[$res] = resolve($this->resources[$res]);
        }

        return $this->controllers[$res];
    }

    public function recurseIncludes(&$records, &$res_fields, &$unprocessed_records, &$included_resource_refs, &$included_relationships)
    {
        $it = 0;
        while (count($unprocessed_records) > 0) {
            $it++;
            $to_relate_lists = [];

            while (count($unprocessed_records) > 0) {

                $record = array_pop($unprocessed_records);
                $record_controller = $this->getResourceController($record->type);
                $record_relationships = $record_controller->relationships();

                $record_fields = $res_fields[$record->type];


                foreach ($record_relationships as $relation_name => $relation) {

                    if (isset($record_fields[$relation_name])) {

                        $relation_compound_key = $record->type.'.'.$relation_name;

                        if (!isset($to_relate_lists[$relation_compound_key])) {
                            $to_relate_lists[$relation_compound_key] = [];
                        }

                        $to_relate_lists[$relation_compound_key][] = $record;
                    }

                }


            }

            $relation_list = [];

            foreach ($to_relate_lists as $relation_compound_key => $relation_records) {

                list($parent_type, $relation_name) = explode('.', $relation_compound_key);
                $relation_controller = $this->getResourceController($parent_type);
                $relation_obj = $relation_controller->relationships()[$relation_name];
                $method_name = $relation_obj->method;

                $pure_records = array_map(function ($record_wrapper) {
                    return $record_wrapper->record;
                }, $relation_records);

                $rel_gen = null;

                if ($this->primary_relation === $relation_compound_key) {
                    $rel_gen = $relation_controller->{$method_name}($pure_records, $this->page);
                } else {
                    $rel_gen = $relation_controller->{$method_name}($pure_records, null);
                }

                foreach ($rel_gen as $yielded) {

                    if ($yielded instanceof Relationship) {

                        $yielded->parent_relation_name = $relation_name;
                        $yielded->child_type = $relation_obj->type;

                        if (!isset($relation_list[$yielded->child_type])) {
                            $relation_list[$yielded->child_type] = [];
                        }

                        $relation_list[$yielded->child_type][] = $yielded;
                        $relations_li = &$records[$yielded->parent_type][$yielded->parent_id]->relations;

                        if (!isset($relations_li[$relation_name])) {
                            $relations_li[$relation_name] = $relation_obj instanceof HasMany
                                ? new ManyRelated()
                                : new OneRelated();
                        }

                        $relations_li[$relation_name]->addRelation($yielded);

                    } else if ($yielded instanceof Record) {

                        $yielded->type = $relation_obj->type;
                        $related_controller = $this->getResourceController($yielded->type);

                        $records[$yielded->type][
                            $related_controller->getIdentifier($yielded->record)
                        ] = $yielded;

                    } else if ($yielded instanceof Link) {

                        $yielded->parent_type = $relation_controller->type();
                        $yielded->parent_relation_name = $relation_name;

                        if (!$yielded->parent_id) {
                            throw new OrphanRelationLink($yielded);
                        }

                        $links = &$records[$yielded->parent_type][$yielded->parent_id]->relation_links;

                        $links[$relation_name] = array_merge(
                            isset($links[$relation_name]) ? $links[$relation_name] : [],
                            $yielded->meta ? [
                                $yielded->name => [
                                    'href' => $yielded->link,
                                    'meta' => $yielded->meta
                                ]
                            ] : [
                                $yielded->name => $yielded->link
                            ]
                        );

                    } else if ($yielded instanceof Meta) {

                        $yielded->parent_type = $relation_controller->type();
                        $yielded->parent_relation_name = $relation_name;

                        if (!$yielded->parent_id) {
                            throw new OrphanRelationMeta($yielded);
                        }

                        $metas = &$records[$yielded->parent_type][$yielded->parent_id]->relation_metas;

                        $metas[$relation_name] = array_merge(
                            isset($metas[$relation_name]) ? $metas[$relation_name] : [],
                            $yielded->meta
                        );
                    }
                }
            }

            foreach ($relation_list as $child_type => $relations) {
                $r_controller = $this->getResourceController($child_type);

                $ids = array_map(function ($relation) {
                    return $relation->child_id;
                }, array_filter($relations, function ($relation) use (&$included_relationships, &$records) {

                    return isset($included_relationships[$relation->parent_type])
                        && isset($included_relationships[$relation->parent_type][
                            $relation->parent_relation_name
                        ]) && (
                            !isset($records[$relation->child_type])
                            || !isset($records[$relation->child_type][$relation->child_id])
                        );

                }));

                foreach ($r_controller->batch($ids) as $record) {
                    $unprocessed_records[] = $record;
                    $id = $r_controller->getIdentifier($record->record);

                    if (!isset($records[$record->type])) {
                        $records[$record->type] = [];
                    }

                    $records[$record->type][$id] = $record;

                    $included_resource_refs[] = [
                        'id' => $id,
                        'type' => $record->type
                    ];
                }
            }
        }

        return $included_resource_refs;
    }

    public function resolveAttrMeta($record, $fields)
    {
        $controller = $this->getResourceController($record->type);

        $meta = [];
        $attrs = [];

        $resolver = $controller->getAttributes($record->record, $fields);

        if ($resolver instanceof Generator) {
            foreach ($resolver as $yielded) {
                if ($yielded instanceof Meta) {
                    $meta = array_merge($meta, $yielded->meta);
                } else if ($yielded instanceof Attribute) {
                    $attrs = array_merge($attrs, [
                        $yielded->name => $yielded->value
                    ]);
                }
            }

            $ret = $resolver->getReturn();

            $attrs = array_merge(
                $attrs,
                $ret ? $ret : []
            );

        } else {
            $attrs = $resolver;
        }

        $selected = [];

        foreach ($fields as $field => $_) {
            if (isset($attrs[$field])) {
                $selected[$field] = $attrs[$field];
            }
        }

        return [$attrs, $meta];
    }

    public function refToResource($ref, &$records, $res_fields)
    {
        $record = $records[$ref['type']][$ref['id']];

        $data = [];
        $data['id'] = $ref['id'];
        $data['type'] = $ref['type'];

        $record = $records[$ref['type']][$ref['id']];

        $fields = $res_fields[$ref['type']];

        list($attrs, $meta) = $this->resolveAttrMeta($record, $fields);
        $data['attributes'] = [];

        if (count($record->relations) > 0) {
            $data['relationships'] = [];

            foreach ($record->relations as $rel_name => $rel) {
                $data['relationships'][$rel_name] = [];
                $data['relationships'][$rel_name]['links'] = isset($record->relation_links[$rel_name])
                    ? $record->relation_links[$rel_name]
                    : [];

                if (isset($record->relation_metas[$rel_name])) {
                    $data['relationships'][$rel_name]['meta'] = $record->relation_metas[$rel_name];
                }

                if ($rel instanceof ManyRelated) {

                    $data['relationships'][$rel_name]['data'] = array_map(function ($r) {
                        return [
                            'type' => $r->child_type,
                            'id' => $r->child_id
                        ];
                    }, $rel->data);

                } else if ($rel instanceof OneRelated) {
                    $data['relationships'][$rel_name]['data'] = null;

                    if ($rel->data && $rel->data->child_id) {
                        $data['relationships'][$rel_name]['data'] = [
                            'type' => $rel->data->child_type,
                            'id' => $rel->data->child_id
                        ];
                    }

                }

                $data['relationships'][$rel_name]['links']['self'] = '/'.$this->base().'/'.$ref['type'].'/'.$ref['id'].'/relationships/'.$rel_name;
                $data['relationships'][$rel_name]['links']['related'] = '/'.$this->base().'/'.$ref['type'].'/'.$ref['id'].'/'.$rel_name;
            }
        }

        if (count($meta) > 0) {
            $data['meta'] = array_merge(
                $meta,
                $record->meta
            );
        }

        foreach ($res_fields[$ref['type']] as $field => $_) {
            if (isset($attrs[$field])) {
                $data['attributes'][$field] = $attrs[$field];
            }
        }

        return $data;
    }

    public function indexRequested($req, $resource_type, $included_relationships, $res_fields)
    {
        $controller = $this->getResourceController($resource_type);
        $meta = [];
        $main_refs = [];
        $records = [];
        $included_resource_refs = [];
        $unprocessed_records = [];
        $links = [];
        $res = [];

        foreach ($controller->index($req, $this->page) as $yielded) {
            if ($yielded instanceof Meta) {

                $meta = array_merge($meta, $yielded->meta);

            } else if ($yielded instanceof Record) {

                $id = $controller->getIdentifier($yielded->record);

                $main_refs[] = [
                    'id' => $id,
                    'type' => $resource_type
                ];

                if (!isset($records[$resource_type])) {
                    $records[$resource_type] = [];
                }

                $records[$resource_type][$id] = $yielded;
                $unprocessed_records[] = $yielded;

            } else if ($yielded instanceof Link) {

                $links[$yielded->name] = $yielded->meta ? [
                    'href' => $yielded->link,
                    'meta' => $yielded->meta
                ] : $yielded->link;

            }
        }

        $included_resource_refs = $this->recurseIncludes(
            $records, $res_fields, $unprocessed_records, $included_resource_refs, $included_relationships
        );

        $main_data = array_map(function ($ref) use (&$records, &$res_fields) {
            return $this->refToResource($ref, $records, $res_fields);
        }, $main_refs);

        $includes_data = array_map(function ($ref) use (&$records, &$res_fields) {
            return $this->refToResource($ref, $records, $res_fields);
        }, $included_resource_refs);

        $res['data'] = $main_data;

        $res = [
            'data' => $main_data,
            'links' => array_merge([
                'self' => $req->getRequestUri(),
            ], $links)
        ];

        if (count($meta) > 0) {
            $res['meta']= $meta;
        }

        if (count($includes_data) > 0) {
            $res['includes'] = $includes_data;
        }

        return $res;
    }

    public function singleRequested($req, $resource_type, $id, $included_relationships, $res_fields)
    {
        $controller = $this->getResourceController($resource_type);
        $meta = [];
        $main_ref = null;
        $records = [];
        $included_resource_refs = [];
        $unprocessed_records = [];
        $links = [];

        foreach ($controller->single($req, $id) as $yielded) {
            if ($yielded instanceof Meta) {

                $meta = array_merge($meta, $yielded->meta);

            } else if ($yielded instanceof Record) {

                $id = $controller->getIdentifier($yielded->record);

                $main_ref = [
                    'id' => $id,
                    'type' => $resource_type
                ];

                if (!isset($records[$resource_type])) {
                    $records[$resource_type] = [];
                }

                $records[$resource_type][$id] = $yielded;
                $unprocessed_records[] = $yielded;

            } else if ($yielded instanceof Link) {

                $links[$yielded->name] = $yielded->meta ? [
                    'href' => $yielded->link,
                    'meta' => $yielded->meta
                ] : $yielded->link;

            }
        }

        $included_resource_refs = $this->recurseIncludes(
            $records, $res_fields, $unprocessed_records, $included_resource_refs, $included_relationships
        );

        $main_data = $this->refToResource($main_ref, $records, $res_fields);

        $includes_data = array_map(function ($ref) use (&$records, &$res_fields) {
            return $this->refToResource($ref, $records, $res_fields);
        }, $included_resource_refs);

        $res = [
            'data' => $main_data,
            'links' => [
                'self' => $req->getRequestUri()
            ]
        ];

        if (count($includes_data) > 0) {
            $res['includes'] = $includes_data;
        }

        return $res;
    }

    public function indexRelationship($req, $resource_type, $id, $relation_name, $included_relationships, $res_fields)
    {
        $res_fields[$resource_type][$relation_name] = true;
        $res = $this->singleRequested($req, $resource_type, $id, $included_relationships, $res_fields);
        $origin = $res['data']['relationships'][$relation_name];
        $res['data'] = $origin['data'];
        $res['links'] = $origin['links'];
        return $res;
    }

    public function indexRelationshipResource($req, $resource_type, $id, $relation_name, $included_relationships, $res_fields)
    {
        $controller = $this->getResourceController($resource_type);

        $res_fields[$resource_type][$relation_name] = true;
        $r = $controller->relationships()[$relation_name];
        $included_relationships[$resource_type][$relation_name] = $r;
        $rel_type = $r->type;

        // dd($included_relationships);


        $meta = [];
        $main_ref = null;
        $records = [];
        $included_resource_refs = [];
        $unprocessed_records = [];
        $links = [];

        foreach ($controller->single($req, $id) as $yielded) {
            if ($yielded instanceof Meta) {

                $meta = array_merge($meta, $yielded->meta);

            } else if ($yielded instanceof Record) {

                $id = $controller->getIdentifier($yielded->record);

                $main_ref = [
                    'id' => $id,
                    'type' => $resource_type
                ];

                if (!isset($records[$resource_type])) {
                    $records[$resource_type] = [];
                }

                $records[$resource_type][$id] = $yielded;
                $unprocessed_records[] = $yielded;

            } else if ($yielded instanceof Link) {

                $links[$yielded->name] = $yielded->meta ? [
                    'href' => $yielded->link,
                    'meta' => $yielded->meta
                ] : $yielded->link;

            }
        }

        $included_resource_refs = $this->recurseIncludes(
            $records, $res_fields, $unprocessed_records, $included_resource_refs, $included_relationships
        );

        $main_record = $records[$resource_type][$id];
        $main_rel = $main_record->relations[$relation_name];
        $main_ref = $main_rel->data;
        $main_ids = [];

        if (is_array($main_ref)) {

            $main_ref = array_map(function ($rel) use (&$main_ids) {
                $main_ids[$rel->child_id] = true;


                return [
                    'type' => $rel->child_type,
                    'id' => $rel->child_id
                ];

            }, $main_ref);

        } else if ($main_ref instanceof Relationship) {

            $main_ref = [
                'type' => $main_ref->child_type,
                'id' => $main_ref->child_id
            ];

            $main_ids[$main_ref['id']] = true;

        }

        $includes_data = array_map(function ($ref) use (&$records, &$res_fields) {

            return $this->refToResource($ref, $records, $res_fields);

        }, array_filter($included_resource_refs, function ($ref) use ($rel_type, &$main_ids) {

            if ($ref['type'] !== $rel_type) {
                return true;
            } else {
                if (isset($main_ids[$ref['id']])) {
                    return false;
                } else {
                    return true;
                }
            }

        }));

        $main_data = [];

        if (!isset($main_ref['type'])) {
            $main_data = array_map(function ($ref) use (&$records, &$res_fields) {
                return $this->refToResource($ref, $records, $res_fields);
            }, $main_ref);
        } else {
            $main_data = $this->refToResource($main_ref, $records, $res_fields);
        }

        $res = [
            'data' => $main_data,
            'links' => $this->refToResource([
                'id' => $id,
                'type' => $resource_type
            ], $records, $res_fields)['relationships'][$relation_name]['links']
        ];

        if (count($includes_data) > 0) {
            $res['includes'] = $includes_data;
        }

        return $res;
    }

    public function resolveIncludedRelationships($resource_type, $included_relationships, $explicit_include_parents)
    {
        $controller = $this->getResourceController($resource_type);
        $relationships = $controller->relationships();

        if (!isset($explicit_include_parents[$resource_type])) {
            $default_includes = $controller->defaultIncludes();

            if (!isset($included_relationships[$resource_type])) {
                $included_relationships[$resource_type] = [];
            }

            foreach ($default_includes as $included_relation_name) {
                $included_relationships[$resource_type][$included_relation_name] = $relationships[$included_relation_name];
            }
        }

        $includes = $included_relationships[$resource_type];

        foreach ($includes as $relation_name => $relationship) {
            $relation_type = $relationship->type;

            if (!isset($included_relationships[$relation_type])) {
                $included_relationships = $this->resolveIncludedRelationships(
                    $relation_type, $included_relationships, $explicit_include_parents
                );
            }
        }

        return $included_relationships;
    }

    public function resolveFields($resource_type, $res_fields, $explicit_field_parents, $included_relationships)
    {
        $controller = $this->getResourceController($resource_type);

        if (!isset($explicit_field_parents[$resource_type])) {
            if (!isset($res_fields[$resource_type])) {
                $res_fields[$resource_type] = [];
            }

            $res_fields[$resource_type] = array_flip(array_unique($controller->defaultFields()));
        }


        $includes = [];

        if (isset($included_relationships[$resource_type])) {
            $includes = $included_relationships[$resource_type];
        }


        foreach ($includes as $relation_name => $relationship) {
            $relation_type = $relationship->type;

            if (!isset($res_fields[$relation_type])) {
                $res_fields = $this->resolveFields(
                    $relation_type,
                    $res_fields,
                    $explicit_field_parents,
                    $included_relationships
                );
            }
        }

        return $res_fields;
    }

    protected function getRelationshipResources($relationships)
    {
        $records = [];

        $loadables = [];
        $addToLoadables = function ($type, $id) use (&$loadables) {
            if (!isset($loadables[$type])) {
                $loadables[$type] = [];
            }

            $loadables[$type][] = $id;
        };

        foreach ($relationships as $relationship_name => $relation_obj) {
            $data = $relation_obj['data'];

            if (!isset($data['type'])) {
                foreach ($data as $ref) {
                    $addToLoadables($ref['type'], $ref['id']);
                }
            } else {
                $addToLoadables($data['type'], $data['id']);
            }
        }

        foreach ($loadables as $type => $ids) {
            $loadable_controller = $this->getResourceController($type);

            foreach ($loadable_controller->batch($ids) as $yielded) {
                if ($yielded instanceof Record) {
                    if (!isset($records[$yielded->type])) {
                        $records[$yielded->type] = [];
                    }

                    $records[$yielded->type][$loadable_controller->getIdentifier($yielded->record)] = $yielded;
                }
            }
        }

        foreach ($relationships as $relationship_name => &$relation_obj) {
            $data = $relation_obj['data'];
            $ndata = [];

            if (!isset($data['type'])) {
                foreach ($data as $ref) {
                    if (isset($records[$ref['type']]) && isset($records[$ref['type']][$ref['id']])) {

                        $ndata[] = $records[$ref['type']][$ref['id']]->record;

                    } else {
                        throw new RelatedResourceNotFound($ref);
                    }
                }
            } else {
                if (isset($records[$data['type']]) && isset($records[$data['type']][$data['id']])) {

                    $ndata = $records[$data['type']][$data['id']]->record;

                } else {
                    throw new RelatedResourceNotFound($data);
                }
            }

            $relation_obj = $ndata;
        }

        return $relationships;
    }

    public function createResource($req, $resource_type, $included_relationships, $res_fields)
    {
        $id = null;
        $controller = $this->getResourceController($resource_type);

        $relationships = $req->input('relationships');
        $relationships = $this->getRelationshipResources($relationships);

        $id = $controller->create($req, $req->input('data', []), $relationships);

        return $this->singleRequested($req, $resource_type, $id, $included_relationships, $res_fields);
    }

    public function updateResource($req, $resource_type, $id, $included_relationships, $res_fields)
    {
        $controller = $this->getResourceController($resource_type);
        $record = null;

        foreach ($controller->batch([$id]) as $yielded) {
            if ($yielded instanceof Record) {
                $record = $yielded;
            }
        }

        if (!$record) {
            throw new ResourceNotFound(['type' => $resource_type, 'id' => $id]);
        }

        $relationships = $req->input('relationships');
        $relationships = $this->getRelationshipResources($relationships);

        $controller->update($req, $record->record, $req->input('data', []), $relationships);

        return $this->singleRequested($req, $resource_type, $id, $included_relationships, $res_fields);
    }

    public function deleteResource($req, $resource_type, $id, $included_relationships, $res_fields)
    {
        $controller = $this->getResourceController($resource_type);
        $record = null;

        foreach ($controller->batch([$id]) as $yielded) {
            if ($yielded instanceof Record) {
                $record = $yielded;
            }
        }

        if (!$record) {
            throw new ResourceNotFound(['type' => $resource_type, 'id' => $id]);
        }

        $meta = [];

        $delete = $controller->delete($req, $record->record);

        if ($delete instanceof Generator) {
            foreach ($delete as $yielded) {
                if ($yielded instanceof Meta) {
                    $meta = array_merge($meta, $yielded->meta);
                }
            }
        }

        return [
            'meta' => $meta
        ];
    }

    public function addResourceRelations($req, $resource_type, $resource_identifier, $relation_name, $included_relationships, $res_fields)
    {
        $controller = $this->getResourceController($resource_type);
        $record = null;

        foreach ($controller->batch([$resource_identifier]) as $yielded) {
            if ($yielded instanceof Record) {
                $record = $yielded;
            }
        }

        if (!$record) {
            throw new ResourceNotFound(['type' => $resource_type, 'id' => $resource_identifier]);
        }

        $relationships = [
            $relation_name => [
                'data' => $req->input('data')
            ]
        ];

        $relationships = $this->getRelationshipResources($relationships);

        $controller->addRelations($record, $relationships);

        return $this->indexRelationship($req, $resource_type, $resource_identifier, $relation_name, $included_relationships, $res_fields);
    }

    public function updateResourceRelation($req, $resource_type, $resource_identifier, $relation_name, $included_relationships, $res_fields)
    {
        $controller = $this->getResourceController($resource_type);
        $record = null;

        foreach ($controller->batch([$resource_identifier]) as $yielded) {
            if ($yielded instanceof Record) {
                $record = $yielded;
            }
        }

        if (!$record) {
            throw new ResourceNotFound(['type' => $resource_type, 'id' => $resource_identifier]);
        }

        $relationships = [
            $relation_name => [
                'data' => $req->input('data')
            ]
        ];

        $relationships = $this->getRelationshipResources($relationships);

        $controller->updateRelations($record, $relationships);

        return $this->indexRelationship($req, $resource_type, $resource_identifier, $relation_name, $included_relationships, $res_fields);
    }

    public function deleteResourceRelation($req, $resource_type, $resource_identifier, $relation_name, $included_relationships, $res_fields)
    {
        $controller = $this->getResourceController($resource_type);
        $record = null;

        foreach ($controller->batch([$resource_identifier]) as $yielded) {
            if ($yielded instanceof Record) {
                $record = $yielded;
            }
        }

        if (!$record) {
            throw new ResourceNotFound(['type' => $resource_type, 'id' => $resource_identifier]);
        }

        $relationships = [
            $relation_name => [
                'data' => $req->input('data')
            ]
        ];

        $relationships = $this->getRelationshipResources($relationships);

        $controller->deleteRelations($record, $relationships);

        return $this->indexRelationship($req, $resource_type, $resource_identifier, $relation_name, $included_relationships, $res_fields);
    }

    public function handle(Request $req)
    {
        $method = strtolower($req->method());

        $base_segment_count = count(array_filter(explode('/', $this->base()), function ($seq) {
            return trim($seq) !== '';
        }));

        $segments = array_slice($req->segments(), $base_segment_count);
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

        if (!isset($this->resources[$resource_type])) {
            return abort(404);
        }

        $included_resource_types = [];
        $explicit_include_parents = [];
        $included_relationships = [];

        $explicit_field_parents = [];
        $res_fields = [];

        $includes = array_filter(explode(',', $req->query('include', '')), function ($include) {
            return strlen(trim($include)) > 0;
        });

        if ($action === GET_RELATED_RESOURCE) {
            $includes[] = $relation_name;
            $includes = array_unique($includes);
        }

        $includeRelationship = function ($type, $relation_name) use (&$included_relationships) {

            if (!isset($included_relationships[$type])) {
                $included_relationships[$type] = [];
            }

            $included_relationships[$type][$relation_name] = $this
                ->getResourceController($type)
                ->relationships()[$relation_name];
        };

        foreach ($includes as $include) {
            if (strpos($include, '.') === false) {
                $explicit_include_parents[] = $resource_type;
                $includeRelationship($resource_type, $include);

            } else {
                list($include_parent_type, $include_relation_name) = explode('.', $include);
                $explicit_include_parents[] = $include_parent_type;

                $includeRelationship($include_parent_type, $include_relation_name);
            }
        }

        $explicit_include_parents = collect($explicit_include_parents)->unique()->flip()->toArray();

        $included_relationships = $this->resolveIncludedRelationships(
            $resource_type, $included_relationships, $explicit_include_parents
        );

        foreach ($req->query('fields', []) as $fieldset_res_type => $fieldset) {
            $explicit_field_parents[$fieldset_res_type] = true;
        }

        $res_fields = $this->resolveFields(
            $resource_type,
            $res_fields,
            $explicit_field_parents,
            $included_relationships
        );

        foreach ($req->query('fields', []) as $fieldset_res_type => $fieldset) {
            if (!isset($res_fields[$fieldset_res_type])) {
                $res_fields[$fieldset_res_type] = [];
            }

            $res_fields[$fieldset_res_type] = array_merge($res_fields[$fieldset_res_type], array_flip(array_unique(array_filter(
                explode(',', $fieldset),
                function ($field) {
                    return strlen(trim($field)) > 0;
                }
            ))));
        }

        $res_fields = array_merge_recursive($res_fields, $included_relationships);
        $page = $req->get('page', 1);
        $this->page = $page;

        $res = null;

        if ($action === GET_RESOURCES) {
            $this->primary_relation = '';
            $res = $this->indexRequested($req, $resource_type, $included_relationships, $res_fields);

        } else if ($action === GET_RESOURCE) {
            $this->primary_relation = '';
            $res = $this->singleRequested($req, $resource_type, $resource_identifier, $included_relationships, $res_fields);

        } else if ($action === GET_RESOURCE_RELATIONSHIP) {
            $this->primary_relation = $resource_type.'.'.$relation_name;
            $res = $this->indexRelationship($req, $resource_type, $resource_identifier, $relation_name, $included_relationships, $res_fields);

        } else if ($action === GET_RELATED_RESOURCE) {
            $this->primary_relation = $resource_type.'.'.$relation_name;
            $res = $this->indexRelationshipResource($req, $resource_type, $resource_identifier, $relation_name, $included_relationships, $res_fields);

        } else if ($action === CREATE_RESOURCE) {
            $this->primary_relation = '';
            $res = $this->createResource($req, $resource_type, $included_relationships, $res_fields);

        } else if ($action === CREATE_RESOURCE_RELATIONSHIP) {

            $this->primary_relation = '';
            $res = $this->addResourceRelations($req, $resource_type, $resource_identifier, $relation_name, $included_relationships, $res_fields);

        } else if ($action === UPDATE_RESOURCE) {

            $this->primary_relation = '';
            $res = $this->updateResource($req, $resource_type, $resource_identifier, $included_relationships, $res_fields);

        } else if ($action === UPDATE_RESOURCE_RELATIONSHIP) {

            $this->primary_relation = '';
            $res = $this->updateResourceRelation($req, $resource_type, $resource_identifier, $relation_name, $included_relationships, $res_fields);

        } else if ($action === DELETE_RESOURCE) {

            $this->primary_relation = '';
            $res = $this->deleteResource($req, $resource_type, $resource_identifier, $included_relationships, $res_fields);

        } else if ($action === DELETE_RESOURCE_RELATIONSHIP) {

            $this->primary_relation = '';
            $res = $this->deleteResourceRelation($req, $resource_type, $resource_identifier, $relation_name, $included_relationships, $res_fields);

        }

        return response()->json($res, 200);
    }
}
