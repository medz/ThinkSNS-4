import { Module } from '@nestjs/common';
import { PrismaModule } from '@socfony/prisma';
import { Context } from './context';

@Module({
  imports: [PrismaModule],
  providers: [Context],
  exports: [Context],
})
export class KernelModule {}
