FROM httpd:2.4

COPY ./docker/apache/apache.vhost.conf /usr/local/apache2/conf/extra/apache.vhost.conf

RUN sed -i \
    -e '/#LoadModule deflate_module/s/^#//g' \
    -e '/#LoadModule proxy_module/s/^#//g' \
    -e '/#LoadModule proxy_fcgi_module/s/^#//g' \
    /usr/local/apache2/conf/httpd.conf

RUN echo "Include /usr/local/apache2/conf/extra/apache.vhost.conf" >> /usr/local/apache2/conf/httpd.conf

COPY . /var/www

RUN chown -R www-data:www-data /var/www && chmod -R 775 /var/www

EXPOSE 80
CMD ["httpd-foreground"]