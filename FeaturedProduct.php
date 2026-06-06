<?php
/**
 * Program 6 — extends: FeaturedProduct extends Product
 * Program 7 — Inheritance: inherits all Product properties and methods,
 *              overrides getBadge(), adds its own featuredLabel and discount.
 *
 * File is loaded automatically by autoload.php (Program 4) because
 * the class name matches the filename.
 */

class FeaturedProduct extends Product   // Program 6: extends
{
    // ── New properties specific to FeaturedProduct ────────────────────────────
    private string $featuredLabel;   // e.g. "Staff Pick", "Best Seller"
    private float  $discountPercent; // e.g. 10.0 for 10 % off

    // ── Program 7: Inheritance — __construct calls parent::__construct ────────
    public function __construct(
        int    $id             = 0,
        string $name           = '',
        string $description    = '',
        float  $price          = 0.0,
        string $image          = '',
        string $category       = '',
        int    $stock          = 0,
        string $created_at     = '',
        string $featuredLabel  = 'Featured',
        float  $discountPercent = 0.0
    ) {
        // Call the parent (Product) constructor — inherits all base setup
        parent::__construct($id, $name, $description, $price, $image, $category, $stock, $created_at);

        $this->featuredLabel   = trim($featuredLabel);
        $this->discountPercent = max(0.0, min(100.0, $discountPercent));
    }

    // ── Program 7: Overriding — getBadge() replaces the parent version ────────
    public function getBadge(): string
    {
        // Use the featured label instead of Value / Popular / Premium
        return $this->featuredLabel;
    }

    // ── Program 7: New method — discounted price calculation ─────────────────
    public function getDiscountedPrice(): float
    {
        return $this->price * (1 - $this->discountPercent / 100);
    }

    // ── Program 7: New method — formatted discounted price ───────────────────
    public function getFormattedDiscountedPrice(): string
    {
        return '$' . number_format($this->getDiscountedPrice(), 2);
    }

    // ── Program 7: New method — check whether a discount is applied ──────────
    public function hasDiscount(): bool
    {
        return $this->discountPercent > 0.0;
    }

    // ── Program 7: __toString override — includes featured label ─────────────
    public function __toString(): string
    {
        return $this->name . ' [' . $this->featuredLabel . ']';
    }
}
?>
