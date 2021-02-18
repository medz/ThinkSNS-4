-- CreateTable
CREATE TABLE "UserProfile" (
    "userId" CHAR(64) NOT NULL,
    "name" VARCHAR(100),
    "avatar" VARCHAR(255),
    "bio" TEXT,
    "location" VARCHAR(255),
    "updatedAt" TIMESTAMP(3) NOT NULL,

    PRIMARY KEY ("userId")
);

-- AddForeignKey
ALTER TABLE "UserProfile" ADD FOREIGN KEY ("userId") REFERENCES "User"("id") ON DELETE CASCADE ON UPDATE CASCADE;
