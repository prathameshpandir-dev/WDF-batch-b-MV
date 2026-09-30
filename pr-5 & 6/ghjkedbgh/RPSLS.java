import java.util.Random;
import java.util.Scanner;

enum Move
{
    ROCK,
    PAPER,
    SCISSORS,
    LIZARD,
    SPOCK
}

public class RPSLS
{
    public static int winner(Move player, Move computer)
    {
        if (player == computer)
        {
            return 0;
        }

        return switch (player)
        {
            case ROCK ->
                (computer == Move.SCISSORS || computer == Move.LIZARD) ? 1 : -1;

            case PAPER ->
                (computer == Move.ROCK || computer == Move.SPOCK) ? 1 : -1;

            case SCISSORS ->
                (computer == Move.PAPER || computer == Move.LIZARD) ? 1 : -1;

            case LIZARD ->
                (computer == Move.SPOCK || computer == Move.PAPER) ? 1 : -1;

            case SPOCK ->
                (computer == Move.SCISSORS || computer == Move.ROCK) ? 1 : -1;
        };
    }

    public static void main(String[] args)
    {
        Scanner sc = new Scanner(System.in);
        Random random = new Random();

        int playerScore = 0;
        int computerScore = 0;

        Move[] moves = Move.values();

        for (int i = 1; i <= 5; i++)
        {
            System.out.println("\nRound " + i);

            System.out.print("Enter Move (ROCK/PAPER/SCISSORS/LIZARD/SPOCK): ");
            Move player = Move.valueOf(sc.next().toUpperCase());

            Move computer = moves[random.nextInt(moves.length)];

            System.out.println("Computer : " + computer);

            int result = winner(player, computer);

            if (result == 1)
            {
                System.out.println("You Win This Round");
                playerScore++;
            }
            else if (result == -1)
            {
                System.out.println("Computer Wins This Round");
                computerScore++;
            }
            else
            {
                System.out.println("Round Draw");
            }
        }

        System.out.println("\nFinal Score");
        System.out.println("You : " + playerScore);
        System.out.println("Computer : " + computerScore);

        if (playerScore > computerScore)
        {
            System.out.println("Overall Winner : You");
        }
        else if (computerScore > playerScore)
        {
            System.out.println("Overall Winner : Computer");
        }
        else
        {
            System.out.println("Match Draw");
        }

        sc.close();
    }
}