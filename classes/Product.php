<?php
/**
 * Product Class
 * 
 * Represents a product entity with properties and methods
 */
class Product {
    private int $id;
    private string $name;
    private string $description;
    private float $price;
    private string $image_url;
    private string $category;
    private int $stock;
    private string $created_at;

    /**
     * Constructor to initialize a Product object
     * 
     * @param int $id
     * @param string $name
     * @param string $description
     * @param float $price
     * @param string $image_url
     * @param string $category
     * @param int $stock
     * @param string $created_at
     */
    public function __construct(int $id, string $name, string $description, float $price, string $image_url, string $category, int $stock, string $created_at) {
        $this->id = $id;
        $this->name = $name;
        $this->description = $description;
        $this->price = $price;
        $this->image_url = $image_url;
        $this->category = $category;
        $this->stock = $stock;
        $this->created_at = $created_at;
    }

    // Getters
    public function getId(): int {
        return $this->id;
    }

    public function getName(): string {
        return $this->name;
    }

    public function getDescription(): string {
        return $this->description;
    }

    public function getPrice(): float {
        return $this->price;
    }

    public function getImageUrl(): string {
        return $this->image_url;
    }

    public function getCategory(): string {
        return $this->category;
    }

    public function getStock(): int {
        return $this->stock;
    }

    public function getCreatedAt(): string {
        return $this->created_at;
    }

    // Setters
    public function setName(string $name): void {
        $this->name = $name;
    }

    public function setDescription(string $description): void {
        $this->description = $description;
    }

    public function setPrice(float $price): void {
        $this->price = $price;
    }

    public function setImageUrl(string $image_url): void {
        $this->image_url = $image_url;
    }

    public function setCategory(string $category): void {
        $this->category = $category;
    }

    public function setStock(int $stock): void {
        $this->stock = $stock;
    }

    /**
     * Magic method to access private properties
     */
    public function __get(string $name) {
        if (property_exists($this, $name)) {
            return $this->$name;
        }
        return null;
    }

    /**
     * Returns the product image URL
     */
    public function getImage(): string {
        return $this->image_url;
    }

    /**
     * Magic method to convert object to string (returns product name)
     */
    public function __toString(): string {
        return $this->name;
    }

    /**
     * Returns badge text based on product age (NEW if added within last 7 days)
     */
    public function getBadge(): string {
        $created = strtotime($this->created_at);
        if ($created === false) {
            return '';
        }

        $now = time();
        $days_old = ($now - $created) / (60 * 60 * 24);
        return $days_old <= 7 ? 'NEW' : '';
    }

    /**
     * Returns truncated description (max 100 characters)
     */
    public function getTruncatedDescription(): string {
        if (strlen($this->description) > 100) {
            return substr($this->description, 0, 100) . '...';
        }
        return $this->description;
    }

    /**
     * Returns formatted date string
     */
    public function getFormattedDate(): string {
        return date('M d, Y', strtotime($this->created_at));
    }

    /**
     * Returns formatted price with currency
     */
    public function getFormattedPrice(): string {
        return '₱' . number_format($this->price, 2);
    }

    /**
     * Checks if product is in stock
     */
    public function isInStock(): bool {
        return $this->stock > 0;
    }
}
