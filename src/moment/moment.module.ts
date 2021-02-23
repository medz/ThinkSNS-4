import { Module } from '@nestjs/common';
import { PrismaModule } from 'src/prisma';

@Module({
  imports: [PrismaModule],
})
export class MomentModule {}
