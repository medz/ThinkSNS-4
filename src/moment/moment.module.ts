import { Module } from '@nestjs/common';
import { PrismaModule } from 'src/prisma';
import { MomentResolver } from './moment.resolver';

@Module({
  imports: [PrismaModule],
  providers: [MomentResolver],
})
export class MomentModule {}
