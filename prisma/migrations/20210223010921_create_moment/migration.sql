-- CreateTable
CREATE TABLE "Moment" (
    "id" CHAR(64) NOT NULL,
    "title" VARCHAR(199),
    "content" TEXT NOT NULL,
    "media" JSONB,
    "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "deletedAt" TIMESTAMP(3),
    "ownerId" CHAR(64) NOT NULL,

    PRIMARY KEY ("id")
);

-- CreateIndex
CREATE INDEX "Moment.createdAt_index" ON "Moment"("createdAt");

-- CreateIndex
CREATE INDEX "Moment.deletedAt_index" ON "Moment"("deletedAt");

-- AddForeignKey
ALTER TABLE "Moment" ADD FOREIGN KEY ("ownerId") REFERENCES "User"("id") ON DELETE CASCADE ON UPDATE CASCADE;
