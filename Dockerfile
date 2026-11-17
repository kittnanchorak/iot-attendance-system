FROM php:8.2-apache

# ติดตั้ง libpq สำหรับเชื่อมต่อ PostgreSQL / Supabase
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd pdo pdo_pgsql pdo_mysql \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# เปิดใช้งาน Apache Rewrite Module
RUN a2enmod rewrite headers

# คัดลอกซอร์สโค้ดเข้า Container
COPY . /var/www/html/

# ตั้งค่าสิทธิ์โฟลเดอร์สำหรับอัปโหลดไฟล์
RUN chown -R www-data:www-data /var/www/html/uploads \
    && chmod -R 775 /var/www/html/uploads

# พอร์ตมาตรฐานของ Apache
EXPOSE 80

CMD ["apache2-foreground"]
