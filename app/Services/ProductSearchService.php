<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;

class ProductSearchService
{
    private const PRODUCT_MATCH = 'MATCH (products.name, products.description, products.variant, products.sub_variant) AGAINST (? IN BOOLEAN MODE)';

    public function apply(Builder $query, string $search): Builder
    {
        $search = $this->normalize($search);

        if ($search === '') {
            return $query;
        }

        $booleanSearch = $this->toBooleanSearch($search);

        $query->where(function (Builder $query) use ($search, $booleanSearch): void {
            $query
                ->where('products.code', $search)
                ->orWhere('products.code', 'like', $search.'%')
                ->orWhere('products.name', 'like', $search.'%')
                ->orWhere('products.variant', 'like', $search.'%')
                ->orWhere('products.sub_variant', 'like', $search.'%')
                ->orWhereRaw(self::PRODUCT_MATCH, [$booleanSearch])
                ->orWhereRaw($this->categoryExists(), [$booleanSearch, $search.'%'])
                ->orWhereRaw($this->attributeExists(), [$booleanSearch, $booleanSearch, $search.'%', $search.'%'])
                ->orWhereRaw($this->variantAttributeExists(), [$booleanSearch, $booleanSearch, $search.'%', $search.'%']);
        });

        $query->selectRaw($this->relevanceExpression(), $this->relevanceBindings($search, $booleanSearch));

        return $query->orderByDesc('search_relevance');
    }

    private function normalize(string $search): string
    {
        return trim((string) preg_replace('/\s+/', ' ', $search));
    }

    private function toBooleanSearch(string $search): string
    {
        return collect(preg_split('/\s+/', $search, -1, PREG_SPLIT_NO_EMPTY))
            ->map(fn (string $term): string => preg_replace('/[^[:alnum:]_]+/u', '', $term))
            ->filter(fn (string $term): bool => $term !== '')
            ->map(fn (string $term): string => '+'.$term)
            ->implode(' ');
    }

    private function relevanceExpression(): string
    {
        return <<<'SQL'
            (
                CASE WHEN products.code = ? THEN 1000 ELSE 0 END
                + CASE WHEN products.name = ? THEN 900 ELSE 0 END
                + CASE WHEN products.code LIKE ? THEN 800 ELSE 0 END
                + CASE WHEN products.name LIKE ? THEN 700 ELSE 0 END
                + CASE WHEN products.variant LIKE ? THEN 600 ELSE 0 END
                + CASE WHEN products.sub_variant LIKE ? THEN 600 ELSE 0 END
                + (MATCH (products.name, products.description, products.variant, products.sub_variant) AGAINST (? IN BOOLEAN MODE) * 100)
                + CASE WHEN EXISTS (
                    SELECT 1 FROM categories AS search_categories
                    WHERE search_categories.id = products.category_id
                    AND (
                        MATCH (search_categories.name) AGAINST (? IN BOOLEAN MODE)
                        OR search_categories.name LIKE ?
                    )
                ) THEN 60 ELSE 0 END
                + CASE WHEN EXISTS (
                    SELECT 1
                    FROM product_attributes AS search_attributes
                    LEFT JOIN product_attribute_options AS search_options
                        ON search_options.product_attribute_id = search_attributes.id
                    WHERE (search_attributes.product_id = products.id OR search_options.product_id = products.id)
                    AND (
                        MATCH (search_attributes.name) AGAINST (? IN BOOLEAN MODE)
                        OR MATCH (search_options.name) AGAINST (? IN BOOLEAN MODE)
                        OR search_attributes.name LIKE ?
                        OR search_options.name LIKE ?
                    )
                ) THEN 50 ELSE 0 END
                + CASE WHEN EXISTS (
                    SELECT 1
                    FROM product_variants AS search_variants
                    INNER JOIN product_variant_attributes AS search_variant_attributes
                        ON search_variant_attributes.product_variant_id = search_variants.id
                    INNER JOIN product_attributes AS search_variant_attributes_lookup
                        ON search_variant_attributes_lookup.id = search_variant_attributes.product_attribute_id
                    INNER JOIN product_attribute_options AS search_variant_options
                        ON search_variant_options.id = search_variant_attributes.product_attribute_option_id
                    WHERE search_variants.product_id = products.id
                    AND (
                        MATCH (search_variant_attributes_lookup.name) AGAINST (? IN BOOLEAN MODE)
                        OR MATCH (search_variant_options.name) AGAINST (? IN BOOLEAN MODE)
                        OR search_variant_attributes_lookup.name LIKE ?
                        OR search_variant_options.name LIKE ?
                    )
                ) THEN 50 ELSE 0 END
            ) AS search_relevance
            SQL;
    }

    private function relevanceBindings(string $search, string $booleanSearch): array
    {
        return [
            $search,
            $search,
            $search.'%',
            $search.'%',
            $search.'%',
            $search.'%',
            $booleanSearch,
            $booleanSearch,
            $search.'%',
            $booleanSearch,
            $booleanSearch,
            $search.'%',
            $search.'%',
            $booleanSearch,
            $booleanSearch,
            $search.'%',
            $search.'%',
        ];
    }

    private function categoryExists(): string
    {
        return <<<'SQL'
            EXISTS (
                SELECT 1 FROM categories AS search_categories
                WHERE search_categories.id = products.category_id
                AND (
                    MATCH (search_categories.name) AGAINST (? IN BOOLEAN MODE)
                    OR search_categories.name LIKE ?
                )
            )
            SQL;
    }

    private function attributeExists(): string
    {
        return <<<'SQL'
            EXISTS (
                SELECT 1
                FROM product_attributes AS search_attributes
                LEFT JOIN product_attribute_options AS search_options
                    ON search_options.product_attribute_id = search_attributes.id
                WHERE (search_attributes.product_id = products.id OR search_options.product_id = products.id)
                AND (
                    MATCH (search_attributes.name) AGAINST (? IN BOOLEAN MODE)
                    OR MATCH (search_options.name) AGAINST (? IN BOOLEAN MODE)
                    OR search_attributes.name LIKE ?
                    OR search_options.name LIKE ?
                )
            )
            SQL;
    }

    private function variantAttributeExists(): string
    {
        return <<<'SQL'
            EXISTS (
                SELECT 1
                FROM product_variants AS search_variants
                INNER JOIN product_variant_attributes AS search_variant_attributes
                    ON search_variant_attributes.product_variant_id = search_variants.id
                INNER JOIN product_attributes AS search_variant_attributes_lookup
                    ON search_variant_attributes_lookup.id = search_variant_attributes.product_attribute_id
                INNER JOIN product_attribute_options AS search_variant_options
                    ON search_variant_options.id = search_variant_attributes.product_attribute_option_id
                WHERE search_variants.product_id = products.id
                AND (
                    MATCH (search_variant_attributes_lookup.name) AGAINST (? IN BOOLEAN MODE)
                    OR MATCH (search_variant_options.name) AGAINST (? IN BOOLEAN MODE)
                    OR search_variant_attributes_lookup.name LIKE ?
                    OR search_variant_options.name LIKE ?
                )
            )
            SQL;
    }
}
