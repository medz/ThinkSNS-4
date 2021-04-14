import { ResolveField, Resolver } from '@nestjs/graphql';
import { Moment, PrismaClient } from '@prisma/client';
import { USER_NOT_FOUND } from 'src/constants';
import { UserEntity } from 'src/user/entities/user.entity';
import { MomentEntity } from '../entities/moment.entity';

@Resolver(() => MomentEntity)
export class MomentEntityFieldsResolver {
  /**
   * The moment entity fields resolver constructor.
   * @param prismaClient Prisma client.
   */
  constructor(private readonly prismaClient: PrismaClient) {}

  /**
   * Resolve owner field.
   * @param parent Parent moment object
   */
  @ResolveField(() => UserEntity)
  owner(parent: Moment) {
    return this.prismaClient.user.findUnique({
      where: { id: parent.ownerId },
      rejectOnNotFound: () => new Error(USER_NOT_FOUND),
    });
  }
}
