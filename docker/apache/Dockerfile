FROM httpd:2.4

COPY ./docker/apache/httpd.conf /usr/local/apache2/conf/httpd.conf
COPY . /var/www

RUN chown -R www-data:www-data /usr/local/apache2/logs
RUN chown -R www-data:www-data /var/www && chmod -R 775 /var/www

EXPOSE 80
CMD ["httpd-foreground"]