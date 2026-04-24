<?php

namespace App\Services;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Parser\MarkdownParser as CommonMarkParser;
use League\CommonMark\Node\Block\Document;
use League\CommonMark\Extension\CommonMark\Node\Block\Heading;
use League\CommonMark\Node\StringContainerInterface;

class MarkdownParser
{
    protected CommonMarkParser $parser;

    public function __construct()
    {
        $environment = new Environment([]);
        $environment->addExtension(new CommonMarkCoreExtension());
        $this->parser = new CommonMarkParser($environment);
    }

    /**
     * Parse markdown and extract sections grouped by headings.
     */
    public function extractStructure(string $markdown): array
    {
        $document = $this->parser->parse($markdown);
        
        $sections = [];
        $currentHeading = 'General';
        $currentContent = '';
        
        $walker = $document->walker();
        
        foreach ($document->children() as $child) {
            if ($child instanceof Heading) {
                // Save previous section
                if (trim($currentContent) !== '') {
                    $sections[] = [
                        'heading' => $currentHeading,
                        'content' => trim($currentContent),
                    ];
                }
                
                // Start new section
                $currentHeading = $this->extractText($child);
                $currentContent = '';
            } else {
                // Accumulate content (simplified approach for textual processing)
                $currentContent .= $this->extractText($child) . "\n\n";
            }
        }
        
        // Save the last section
        if (trim($currentContent) !== '') {
            $sections[] = [
                'heading' => $currentHeading,
                'content' => trim($currentContent),
            ];
        }
        
        return $sections;
    }

    /**
     * Recursively extract text from a node
     */
    protected function extractText($node): string
    {
        $text = '';
        if ($node instanceof StringContainerInterface) {
            $text .= $node->getLiteral();
        }
        
        if (method_exists($node, 'children')) {
            foreach ($node->children() as $child) {
                $text .= $this->extractText($child);
            }
        }
        
        return $text;
    }
}
