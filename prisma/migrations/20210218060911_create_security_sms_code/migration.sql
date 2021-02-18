-- CreateTable
CREATE TABLE "SecuritySmsCode" (
    "id" CHAR(64) NOT NULL,
    "phone" VARCHAR(199) NOT NULL,
    "code" VARCHAR(50) NOT NULL,
    "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "expiredAt" TIMESTAMP(3) NOT NULL,
    "usedAt" TIMESTAMP(3),

    PRIMARY KEY ("id")
);

-- CreateIndex
CREATE INDEX "SecuritySmsCode.phone_index" ON "SecuritySmsCode"("phone");

-- CreateIndex
CREATE INDEX "SecuritySmsCode.code_index" ON "SecuritySmsCode"("code");

-- CreateIndex
CREATE INDEX "SecuritySmsCode.usedAt_index" ON "SecuritySmsCode"("usedAt");
