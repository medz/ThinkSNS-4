# 打包代码
FROM node:lts-alpine as builder
WORKDIR /app
COPY ./ ./
RUN npm install
RUN npm run build

# 构建环境
FROM node:lts-alpine

RUN apk --no-cache add openssl

WORKDIR /app
COPY --form=builder /socfony/dist /app
EXPOSE 4000
CMD ["node", "index.js"]
