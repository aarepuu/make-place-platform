#
# Builds an instance of the app from the base adding the variable parts (code)
#


# Start from our base image
FROM openlab.ncl.ac.uk:4567/make-place/web:base-2.4.1


# Add files to the build
COPY . /app/


# Add composer & cronjobs files
RUN mv _config/bootstrap.sh bootstrap.sh \
  && mv _config/phpunit.xml phpunit.xml \
  && mv _config/default.nginx /etc/nginx/sites-available/default \
  && crontab -u root _config/cronjobs \
  && npm run build > /dev/null \
  && scripts/build-docs > /dev/null
