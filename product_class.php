<?php

interface Priceable {
    public function getFormattedPrice(): string;
    public function getDiscountedPrice(): float;
}

abstract class AbstractProduct implements Priceable {
    protected int $id;
    protected string $name;
    protected string $description;
    protected float $price;
    protected string $image;
    protected string $category;
    protected int $stock;
    protected string $created_at;

    public function __construct(
        int $id = 0,
        string $name = '',
        string $description = '',
        float $price = 0.0,
        string $image = '',
        string $category = '',
        int $stock = 0,
        string $created_at = ''
    ) {
        $this->id          = $id;
        $this->name        = trim($name);
        $this->description = trim($description);
        $this->price       = $price;
        $this->image       = $image;
        $this->category    = $category;
        $this->stock       = $stock;
        $this->created_at  = $created_at;
    }

    public function __toString(): string {
        return $this->name;
    }

    public function __get(string $property) {
        if (property_exists($this, $property)) {
            return $this->$property;
        }
        trigger_error("Property '$property' does not exist", E_USER_WARNING);
        return null;
    }

    public function __set(string $property, $value) {
        if (!property_exists($this, $property)) {
            trigger_error("Property '$property' does not exist", E_USER_WARNING);
            return;
        }
        switch ($property) {
            case 'name':
                $value = trim($value);
                if (strlen($value) > 150) {
                    trigger_error("Product name cannot exceed 150 characters", E_USER_WARNING);
                    return;
                }
                break;
            case 'price':
                $value = (float) $value;
                if ($value < 0) {
                    trigger_error("Price cannot be negative", E_USER_WARNING);
                    return;
                }
                break;
            case 'stock':
                $value = (int) $value;
                if ($value < 0) {
                    trigger_error("Stock cannot be negative", E_USER_WARNING);
                    return;
                }
                break;
        }
        $this->$property = $value;
    }

    public function __clone() {
        $this->id   = 0;
        $this->name = "Copy of " . $this->name;
    }

    public function getFormattedPrice(): string {
        return '₱' . number_format($this->price, 2);
    }

    public function getDiscountedPrice(): float {
        return $this->price;
    }

    public function getImage(): string {
        if (!empty($this->image)) {
            return $this->image;
        }
        return "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 200 200'%3E%3Crect width='200' height='200' fill='%23f5f5f5'/%3E%3Crect x='30' y='50' width='140' height='100' fill='%23333' rx='8'/%3E%3Crect x='40' y='60' width='120' height='80' fill='%23555' rx='4'/%3E%3Ccircle cx='100' cy='100' r='20' fill='%231a1a1a'/%3E%3C/svg%3E";
    }

    public function getTruncatedDescription(int $length = 120): string {
        if (empty($this->description)) return 'No description available.';
        if (strlen($this->description) > $length) {
            return substr($this->description, 0, $length) . '...';
        }
        return $this->description;
    }

    public function getFormattedDate(): string {
        if (empty($this->created_at)) return '';
        $timestamp = strtotime($this->created_at);
        if ($timestamp === false) return '';
        return date('M j, Y', $timestamp);
    }

    public function isInStock(): bool {
        return $this->stock > 0;
    }

    public function toArray(): array {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'price'       => $this->price,
            'description' => $this->description,
            'image'       => $this->image,
            'category'    => $this->category,
            'stock'       => $this->stock,
            'created_at'  => $this->created_at,
        ];
    }

    abstract public function getBadge(): string;
}

class Product extends AbstractProduct {

    public function getBadge(): string {
        if ($this->price < 100) return 'Value';
        if ($this->price < 300) return 'Popular';
        return 'Premium';
    }

    public static function fromArray(array $row): self {
        return new self(
            (int)($row['id'] ?? 0),
            $row['name'] ?? '',
            $row['description'] ?? '',
            (float)($row['price'] ?? 0.0),
            $row['image_url'] ?? $row['image'] ?? '',
            $row['category'] ?? '',
            (int)($row['stock'] ?? 0),
            $row['created_at'] ?? ''
        );
    }

    public static function getCategories(): array {
        return [
            'PC Components & GPUs',
            'Laptops & Monitors',
            'Gaming Peripherals',
            'Smartphones & Accessories',
            'Audio & Headphones',
        ];
    }

    public function matchesSearch(string $keyword): bool {
        $keyword = trim($keyword);
        if (empty($keyword)) return true;
        $keyword_lower = strtolower($keyword);
        return strpos(strtolower($this->name), $keyword_lower) !== false
            || strpos(strtolower($this->description), $keyword_lower) !== false;
    }
}
?>