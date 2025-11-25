FROM node:24-alpine

WORKDIR /app

COPY ./producer/package*.json ./
RUN npm install --production

COPY ./producer ./         
# zamiast ./producer
COPY ./utils ./utils
COPY ./schemas ./schemas

CMD ["node", "index.js"]
