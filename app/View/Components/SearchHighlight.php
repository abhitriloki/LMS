<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class SearchHighlight extends Component
{
    public string $text;
    public ?string $search;

    /**
     * Create a new component instance.
     */
    public function __construct(string $text, ?string $search = null)
    {
        $this->text = $text;
        $this->search = $search ?? request('search');
    }

    /**
     * Get the highlighted text
     */
    public function getHighlightedText(): string
    {
        if (empty($this->search)) {
            return e($this->text);
        }

        $pattern = '/(' . preg_quote($this->search, '/') . ')/i';
        $replacement = '<mark class="bg-yellow-200 dark:bg-yellow-800 text-gray-900 dark:text-white px-1 rounded">$1</mark>';
        
        return preg_replace($pattern, $replacement, e($this->text));
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View
    {
        return view('components.search-highlight');
    }
}
