<?php

class TemplateEngine {
    public function render($templatePath, $data, $attributes = []) {
        // Check if the path is a URL
        if (filter_var($templatePath, FILTER_VALIDATE_URL)) {
            $template = file_get_contents($templatePath);
            if ($template === false) {
                return "Error: Unable to load remote template.";
            }
        } else {
            if (!file_exists($templatePath)) {
                return "Error: Template file not found.";
            }
            $template = file_get_contents($templatePath);
        }

        // Process <template> blocks for loops
        $template = $this->processTemplateBlocks($template, $data);

        // Process remaining placeholders
        $rendered = $this->processPlaceholders($template, $data);

        // Process attributes
        $rendered = $this->processAttributes($rendered, $attributes);

        return $rendered;
    }

    private function processTemplateBlocks($template, $data) {
        return preg_replace_callback(
            '/<template\s+id="(\w+)"\s*>(.*?)<\/template>/s',
            function($matches) use ($data) {
                $arrayKey  = $matches[1];
                $loopBlock = $matches[2];
                $result    = '';

                if (isset($data[$arrayKey]) && is_array($data[$arrayKey])) {
                    foreach ($data[$arrayKey] as $item) {
                        $result .= preg_replace_callback('/\{([^}]+)\}/', function($m) use ($item) {
                            $key = $m[1];
                            return isset($item[$key]) ? $item[$key] : $m[0];
                        }, $loopBlock);
                    }
                }
                return $result;
            },
            $template
        );
    }

    private function processPlaceholders($template, $data) {
        return preg_replace_callback('/\{([^}]+)\}/', function($matches) use ($data) {
            $key = $matches[1];
            return isset($data[$key]) ? $data[$key] : $matches[0];
        }, $template);
    }

    private function processAttributes($template, $attributes) {
        foreach ($attributes as $tag => $attr) {
            $template = preg_replace_callback(
                '/<'.$tag.'([^>]*)>/',
                function($matches) use ($tag, $attr) {
                    $tagContent = $matches[1];
                    foreach ($attr as $key => $value) {
                        if (!preg_match('/\b'.$key.'\s*=/', $tagContent)) {
                            $tagContent .= " $key=\"$value\"";
                        }
                    }
                    return "<$tag$tagContent>";
                },
                $template
            );
        }
        return $template;
    }
}

?>

