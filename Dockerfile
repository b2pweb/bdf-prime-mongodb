FROM docker-registry.b2pweb.com:5000/integration/b2p-integration:7

MAINTAINER Didier FABERT <dfabert@b2pweb.com>

ENV APP_PATH=/home/bdf-prime-mongodb

#######  Integration ##########################################################

# Copy project to container image
COPY src/ ${APP_PATH}/src/
COPY build/ ${APP_PATH}/build/
COPY tests/ ${APP_PATH}/tests/
COPY composer.json ${APP_PATH}/
#COPY composer.lock ${APP_PATH}/
COPY release.txt ${APP_PATH}/
COPY phpunit.xml.dist ${APP_PATH}/

COPY sonar-project.properties ${APP_PATH}/
COPY build/integration.sh /ci.sh

# Create basic report directories
RUN mkdir -p ${APP_PATH}/build/reports/coverage \
            ${APP_PATH}/build/reports/selenium \
            ${APP_PATH}/build/apidocs \
            ${APP_PATH}/release/old

CMD [ "/ci.sh", "${APP_PATH}" ]
