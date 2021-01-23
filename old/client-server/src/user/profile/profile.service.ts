import { Injectable } from '@nestjs/common';
import { ID } from '@socfony/kernel';
import { PrismaClient, User, UserProfile } from '@socfony/prisma';

@Injectable()
export class UserProfileService {
  constructor(private readonly prisma: PrismaClient) {}

  /**
   * Query or create user profile.
   * @param user Need fetch profile user.
   */
  async resolveProfile(user: User): Promise<UserProfile> {
    const profile = await this.prisma.userProfile.findUnique({
      where: { userId: user.id },
    });
    if (!profile) {
      return await this.prisma.userProfile.create({
        data: {
          id: ID.generator(32),
          userId: user.id,
        },
      });
    }

    return profile;
  }
}
