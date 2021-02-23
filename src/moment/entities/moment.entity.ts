import { Field, GraphQLISODateTime, ID, ObjectType } from '@nestjs/graphql';
import { Prisma, User } from '@prisma/client';
import { UserUnion } from 'src/user/entities/user.union';
import { MomentMedia } from './media.entity';

/**
 * Moment entity.
 */
@ObjectType({
  description: 'Moment entity',
})
export class MomentEntity
  implements
    Prisma.MomentGetPayload<{
      include: {
        owner: true;
      };
    }> {
  /**
   * Moment ID
   */
  @Field(() => ID, { description: 'The moment ID' })
  id: string;

  /**
   * Moment title
   */
  @Field(() => String, { description: 'Moment title', nullable: true })
  title: string;

  /**
   * Moment text content
   */
  @Field(() => String, { description: 'Moment content' })
  content: string;

  /**
   * Moment media
   */
  @Field(() => MomentMedia, { nullable: true, description: 'Moment media' })
  media: Prisma.JsonValue;

  /**
   * Moment created at
   */
  @Field(() => GraphQLISODateTime, { description: 'Moment created at' })
  createdAt: Date;

  /**
   * Moment deleted at
   */
  @Field(() => GraphQLISODateTime, { description: 'Moment deleted at' })
  deletedAt: Date;

  /**
   * Moment owner ID
   */
  @Field(() => ID, { description: 'Moment owner ID' })
  ownerId: string;

  /**
   * Moment owner
   */
  @Field(() => UserUnion, { description: 'The moment owner' })
  owner: User;
}
