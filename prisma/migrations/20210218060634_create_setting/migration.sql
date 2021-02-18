-- CreateTable
CREATE TABLE "Setting" (
    "namespace" VARCHAR(199) NOT NULL,
    "name" VARCHAR(199) NOT NULL,
    "value" JSONB NOT NULL,
    "updatedAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY ("namespace","name")
);

-- CreateIndex
CREATE INDEX "Setting.namespace_index" ON "Setting"("namespace");

-- CreateIndex
CREATE INDEX "Setting.name_index" ON "Setting"("name");
