-- CreateTable
CREATE TABLE "User" (
    "id" CHAR(64) NOT NULL,
    "login" VARCHAR(199),
    "email" VARCHAR(199),
    "phone" VARCHAR(199),
    "password" VARCHAR(255),
    "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY ("id")
);

-- CreateIndex
CREATE UNIQUE INDEX "User.login_unique" ON "User"("login");

-- CreateIndex
CREATE UNIQUE INDEX "User.email_unique" ON "User"("email");

-- CreateIndex
CREATE UNIQUE INDEX "User.phone_unique" ON "User"("phone");

-- CreateIndex
CREATE INDEX "User.createdAt_index" ON "User"("createdAt");
