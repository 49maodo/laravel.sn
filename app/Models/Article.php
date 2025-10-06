<?php

namespace App\Models;

use App\Enums\ArticleStatus;
use App\Observers\ArticleObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use League\CommonMark\CommonMarkConverter;

#[ObservedBy([ArticleObserver::class])]
class Article extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'slug', 'content', 'status', 'published_at', 'category_id'];

    protected $casts = [
        'status' => ArticleStatus::class,
        'published_at' => 'datetime',
    ];

    /**
     * Get the category that owns the article.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the article content as HTML (parsed from markdown).
     */
    protected function contentHtml(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                $converter = new CommonMarkConverter([
                    'html_input' => 'strip',
                    'allow_unsafe_links' => false,
                    'max_nesting_level' => 10,
                ]);

                $html = $converter->convert($this->content)->getContent();

                // Add Prism.js classes to code blocks for syntax highlighting
                $html = preg_replace(
                    '/<pre><code class="language-([^"]+)">/',
                    '<pre class="language-$1"><code class="language-$1">',
                    $html
                );

                // Handle code blocks without language specification
                $html = preg_replace(
                    '/<pre><code>/',
                    '<pre class="language-text"><code class="language-text">',
                    $html
                );

                return $html;
            }
        );
    }

    /**
     * Get a plain text excerpt of the article content.
     */
    protected function excerpt(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                // Strip markdown syntax and get first 150 characters
                $plainText = strip_tags($this->content_html);

                return Str::limit($plainText, 150);
            }
        );
    }
}
