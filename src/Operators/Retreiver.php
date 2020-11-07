<?php

namespace Bonton\Japi\Operators;

use Bonton\Japi\Factory;
use Bonton\Japi\MetaPacket;
use Bonton\Japi\RecordPacket;
use Bonton\Japi\Resource;
use Bonton\Japi\ResourceStore;
use Generator;
use Illuminate\Http\Request;

class Retreiver
{
    protected $all_resources;
    protected $resolved_resources;

    protected $main_resources = [];
    protected $includes;

    protected $resolution_stack;

    protected $factory;
    protected $graph;

    public function __construct(Factory $factory)
    {
        $this->factory = $factory;
        $this->graph = null;
        $this->all_resources = new ResourceStore();
        $this->resolved_resources = new ResourceStore();
        $this->resolution_stack = collect([]);
        $this->includes = collect([]);
    }

    protected function generalize($res)
    {
        $arr = $res;

        if ($res instanceof Generator) {
            foreach ($res as $yielded) {
                yield $yielded;
            }

            $arr = $res->getReturn();
        }

        if (is_array($arr)) {
            foreach ($arr as $yieldable) {
                yield new RecordPacket($yieldable);
            }
        }
    }

    public function index(Request $req, string $type)
    {
        $meta = [];
        $resolver = $this->factory->getResolver($type);
        $companion = $this->factory->getCompanion($type);

        $records = $this->generalize(
            $resolver->index($req)
        );

        foreach ($records as $yielded) {
            if ($yielded instanceof RecordPacket) {
                $res = new Resource($type, $companion->key($yielded->record), $yielded->record);
                $res->main();

                $linkage = $res->linkage();

                $this->all_resources->store($res);
                $this->main_resources[] = $linkage;
                $this->resolution_stack->push($linkage);

            } else if ($yielded instanceof MetaPacket) {
                $meta = array_merge($meta, $yielded->meta);
            }
        }

        $this->processStack();
    }

    protected function processStack()
    {
        while ($this->resolution_stack->count() > 0) {
            $typed = $this->resolution_stack->groupBy(function ($entry) {
                return $entry->type;
            });

            $this->resolution_stack = collect([]);

            foreach ($typed as $type => $linkages) {
                // Check if type has included relationships || relationship field
                // If yes, check if not already resolved
                // load relationships
                //     populate the resource for each relationship
                //     handle meta & links
                //     add to resolution stack

                foreach ($linkages as $linkage) {
                    $resource = $this->all_resources->get($linkage);

                    if (!$resource->isMain()) {
                        $this->includes->push($resource);
                    }
                }
            }            
        }
    }
}
