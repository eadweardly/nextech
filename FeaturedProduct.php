<?php

class FeaturedProduct extends Product  
{
    private string $featuredLabel;   
    private float  $discountPercent; 
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
        parent::__construct($id, $name, $description, $price, $image, $category, $stock, $created_at);

        $this->featuredLabel   = trim($featuredLabel);
        $this->discountPercent = max(0.0, min(100.0, $discountPercent));
    }

    public function getBadge(): string
    {
        return $this->featuredLabel;
    }

    public function getDiscountedPrice(): float
    {
        return $this->price * (1 - $this->discountPercent / 100);
    }

    public function getFormattedDiscountedPrice(): string
    {
        return '$' . number_format($this->getDiscountedPrice(), 2);
    }

    public function hasDiscount(): bool
    {
        return $this->discountPercent > 0.0;
    }

    public function __toString(): string
    {
        return $this->name . ' [' . $this->featuredLabel . ']';
    }
}
?>
