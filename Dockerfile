FROM php:8.3-cli

WORKDIR /app

# ZIP ichidagi papka strukturasi:
# manhwauz.com - Copy/
#   WEB/
#   WEBAD/
#   DATA/

COPY . .

EXPOSE 8090

CMD php -S 0.0.0.0:8090 WEB/router.php
