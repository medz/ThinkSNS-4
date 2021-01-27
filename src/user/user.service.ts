import { Injectable } from '@nestjs/common';
import { PrismaClient, User } from '@prisma/client';
import { compare as comparePassword } from 'bcrypt';

@Injectable()
export class UserService {
  constructor(private readonly prisma: PrismaClient) {}

  /**
   * Compore user password.
   * @param user Need compare password user.
   * @param password input password.
   */
  async comparePassword(user: User, password: string): Promise<boolean> {
    if (!user.password) return false;
    return await comparePassword(password, user.password);
  }
}
