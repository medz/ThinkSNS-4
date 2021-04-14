import { Module } from '@nestjs/common';
import { PrismaModule } from 'src/prisma';
import { MomentEntityFieldsResolver } from './resolvers/moment-entity-fields.resolver';
import { MomentQueryResolver } from './resolvers/moment-query.resolver';

@Module({
  imports: [PrismaModule],
  providers: [MomentEntityFieldsResolver, MomentQueryResolver],
})
export class MomentModule {}
