<?php

declare(strict_types=1);

namespace App\UI;

use Illuminate\Contracts\Support\Htmlable;

/**
 * Example Htmlable class for tab content
 */
class HtmlableExample implements Htmlable
{
    public function __construct(
        private string $title,
        private string $content,
        private array $data = []
    ) {}

    public function toHtml(): string
    {
        $dataRows = '';
        foreach ($this->data as $key => $value) {
            $dataRows .= "<tr><td class='px-4 py-2 border-b border-gray-200 font-medium'>{$key}</td><td class='px-4 py-2 border-b border-gray-200'>{$value}</td></tr>";
        }

        return "
            <div class='bg-indigo-50 border border-indigo-200 rounded-lg p-6'>
                <h3 class='text-indigo-800 font-semibold text-lg mb-4'>📄 {$this->title}</h3>
                <p class='text-indigo-700 mb-6'>{$this->content}</p>
                
                <div class='bg-white rounded-lg p-4 shadow-sm'>
                    <h4 class='font-semibold text-gray-900 mb-3'>Data Table:</h4>
                    <table class='w-full'>
                        <thead>
                            <tr class='bg-gray-50'>
                                <th class='px-4 py-2 text-left font-semibold text-gray-700'>Property</th>
                                <th class='px-4 py-2 text-left font-semibold text-gray-700'>Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            {$dataRows}
                        </tbody>
                    </table>
                </div>
                
                <div class='mt-4 p-3 bg-indigo-100 rounded'>
                    <p class='text-sm text-indigo-800'>
                        💡 <strong>Htmlable Content:</strong> This tab content was generated using an Htmlable class 
                        instead of a Closure, demonstrating the flexibility of the content system.
                    </p>
                </div>
            </div>
        ";
    }

    public static function create(string $title, string $content, array $data = []): self
    {
        return new self($title, $content, $data);
    }
}