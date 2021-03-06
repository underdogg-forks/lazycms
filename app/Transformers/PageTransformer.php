<?php

namespace App\Transformers;

use Storage;
use Carbon\Carbon;
use Ramsey\Uuid\Uuid;
use Mni\FrontYAML\Parser as YAMLParser;

class PageTransformer implements Transformer {

    private $parser;

    public function __construct(YAMLParser $parser)
    {
        $this->parser = $parser;
    }

    public function transform($toTransform)
    {
        return $toTransform->map(function($pageFilePath) {
            $page = $this->getPageItems($pageFilePath);

            return [
                'title'           => $this->getTitle($page),
                'slug'            => $this->getSlug($page),
                'use_layout'      => $this->getLayout($page),
                'meta_content'    => json_encode(['meta' => $page['yaml']['meta']]),
                'content'         => json_encode(['body' => $page['content']]),
                'created_at'      => Carbon::now(),
                'updated_at'      => Carbon::now(),
            ];
        });
    }

    private function getAttribute($post, $itemName, $default)
    {
        return isset($post['yaml'][$itemName]) ? $post['yaml'][$itemName] : $post[$default];
    }

    private function getTitle($post)
    {
        return $this->getAttribute($post, 'title', 'filename');
    }


    private function getPageItems($listing)
    {
        $file = Storage::disk('dropbox')->get($listing);
        $parsedFile = $this->parser->parse($file);

        return [
            'filename' => $this->cleanFileName($listing),
            'yaml' => $parsedFile->getYaml(),
            'content' => $parsedFile->getContent()
        ];
    }

    private function getSlug($post)
    {
        return $this->getAttribute($post, 'slug', 'filename');
    }

    private function cleanFileName($item)
    {
        $item = str_replace(getenv('DROPBOX_PAGE_PATH').'/','', $item);
        return str_replace(getenv('DROPBOX_PAGE_EXTENSION'), '', $item);
    }

    private function getLayout($post)
    {
        return $this->getAttribute($post, 'use_layout', getenv('DEFAULT_PAGE_LAYOUT'));
    }

}
