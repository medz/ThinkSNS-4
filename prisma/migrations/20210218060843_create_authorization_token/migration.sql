-- CreateTable
CREATE TABLE "AuthorizationToken" (
    "userId" CHAR(64) NOT NULL,
    "token" CHAR(128) NOT NULL,
    "expiredAt" TIMESTAMP(3) NOT NULL,
    "refreshExpiredAt" TIMESTAMP(3) NOT NULL,

    PRIMARY KEY ("token")
);

-- CreateIndex
CREATE INDEX "AuthorizationToken.userId_index" ON "AuthorizationToken"("userId");

-- CreateIndex
CREATE INDEX "AuthorizationToken.expiredAt_index" ON "AuthorizationToken"("expiredAt");

-- CreateIndex
CREATE INDEX "AuthorizationToken.refreshExpiredAt_index" ON "AuthorizationToken"("refreshExpiredAt");

-- AddForeignKey
ALTER TABLE "AuthorizationToken" ADD FOREIGN KEY ("userId") REFERENCES "User"("id") ON DELETE CASCADE ON UPDATE CASCADE;
