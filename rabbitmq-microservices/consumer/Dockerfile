FROM node:24-alpine

WORKDIR /app

COPY ./consumer/package*.json ./
RUN npm install --production

COPY ./consumer ./         
# zamiast ./consumer
COPY ./utils ./utils
COPY ./db ./db
COPY ./schemas ./schemas

CMD ["node", "index.js"]
