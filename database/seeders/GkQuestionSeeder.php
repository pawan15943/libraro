<?php

namespace Database\Seeders;

use App\Models\GkQuestion;
use Illuminate\Database\Seeder;

/** Starter bank for the Libraro daily GK quiz. Safe to run again: existing questions are skipped. */
class GkQuestionSeeder extends Seeder
{
    public function run(): void
    {
        // [category, question, a, b, c, d, correct, explanation]
        $rows = [
            ['Polity', 'Who is called the Father of the Indian Constitution?', 'Mahatma Gandhi', 'B. R. Ambedkar', 'Jawaharlal Nehru', 'Sardar Patel', 'b', 'Dr. B. R. Ambedkar chaired the Drafting Committee.'],
            ['Polity', 'How many fundamental rights are there in the Indian Constitution now?', '5', '6', '7', '8', 'b', 'Right to Property was removed in 1978, leaving six.'],
            ['Polity', 'The Indian Constitution came into force on:', '15 August 1947', '26 November 1949', '26 January 1950', '2 October 1950', 'c', 'Republic Day is celebrated on 26 January.'],
            ['Polity', 'Who appoints the Chief Justice of India?', 'Prime Minister', 'President', 'Parliament', 'Law Minister', 'b', 'The President appoints the CJI.'],
            ['Polity', 'The minimum age to become a member of the Lok Sabha is:', '21 years', '25 years', '30 years', '35 years', 'b', 'Rajya Sabha needs 30 years.'],
            ['Polity', 'How many members can the President nominate to the Rajya Sabha?', '2', '8', '12', '14', 'c', 'Twelve members from art, science, literature and social service.'],
            ['Geography', 'Which is the longest river in India?', 'Yamuna', 'Ganga', 'Godavari', 'Brahmaputra', 'b', 'The Ganga is about 2,525 km long.'],
            ['Geography', 'Which is the highest peak in India?', 'Nanda Devi', 'Kangchenjunga', 'K2', 'Mount Everest', 'b', 'Kangchenjunga (8,586 m) is the highest peak within India.'],
            ['Geography', 'Which state has the longest coastline in India?', 'Tamil Nadu', 'Maharashtra', 'Gujarat', 'Andhra Pradesh', 'c', 'Gujarat has about 1,600 km of coastline.'],
            ['Geography', 'The Tropic of Cancer does NOT pass through which state?', 'Gujarat', 'Madhya Pradesh', 'Odisha', 'Jharkhand', 'c', 'It passes through 8 states, but not Odisha.'],
            ['Geography', 'Which is the largest state of India by area?', 'Madhya Pradesh', 'Maharashtra', 'Rajasthan', 'Uttar Pradesh', 'c', 'Rajasthan covers about 342,239 sq km.'],
            ['Geography', 'Chilika Lake is located in which state?', 'Kerala', 'Odisha', 'West Bengal', 'Andhra Pradesh', 'b', 'It is the largest coastal lagoon in India.'],
            ['History', 'Who was the first Governor-General of independent India?', 'Lord Mountbatten', 'C. Rajagopalachari', 'Rajendra Prasad', 'Lord Wavell', 'a', 'Mountbatten served until June 1948.'],
            ['History', 'The Battle of Plassey was fought in:', '1757', '1764', '1857', '1526', 'a', 'Robert Clive defeated Siraj-ud-Daulah.'],
            ['History', 'Who founded the Mauryan Empire?', 'Ashoka', 'Bindusara', 'Chandragupta Maurya', 'Samudragupta', 'c', 'Chandragupta founded it around 321 BCE with Chanakya.'],
            ['History', 'The Quit India Movement started in:', '1930', '1942', '1946', '1920', 'b', 'It began on 8 August 1942.'],
            ['History', 'Who wrote the book "Discovery of India"?', 'Mahatma Gandhi', 'Jawaharlal Nehru', 'Subhas Chandra Bose', 'Rabindranath Tagore', 'b', 'Nehru wrote it in Ahmednagar Fort prison.'],
            ['History', 'The Jallianwala Bagh massacre took place in:', '1915', '1919', '1922', '1930', 'b', 'It happened in Amritsar on 13 April 1919.'],
            ['Science', 'What is the chemical symbol of gold?', 'Ag', 'Au', 'Gd', 'Go', 'b', 'Au comes from the Latin "aurum".'],
            ['Science', 'Which gas do plants absorb from the air for photosynthesis?', 'Oxygen', 'Nitrogen', 'Carbon dioxide', 'Hydrogen', 'c', 'Plants use CO2 and water to make glucose.'],
            ['Science', 'The SI unit of force is:', 'Joule', 'Newton', 'Pascal', 'Watt', 'b', 'One newton = 1 kg·m/s².'],
            ['Science', 'Which vitamin is produced in the skin by sunlight?', 'Vitamin A', 'Vitamin B12', 'Vitamin C', 'Vitamin D', 'd', 'Sunlight helps the skin make Vitamin D.'],
            ['Science', 'The human heart has how many chambers?', '2', '3', '4', '5', 'c', 'Two atria and two ventricles.'],
            ['Science', 'What is the speed of light in vacuum (approx.)?', '3 × 10^6 m/s', '3 × 10^8 m/s', '3 × 10^10 m/s', '3 × 10^5 m/s', 'b', 'About 300,000 km per second.'],
            ['Science', 'Which planet is known as the Red Planet?', 'Venus', 'Jupiter', 'Mars', 'Saturn', 'c', 'Iron oxide on its surface gives the red colour.'],
            ['Economy', 'Who regulates the monetary policy of India?', 'SEBI', 'Reserve Bank of India', 'Finance Ministry', 'NITI Aayog', 'b', 'The RBI sets the repo rate through the MPC.'],
            ['Economy', 'GST came into effect in India on:', '1 April 2016', '1 July 2017', '1 April 2018', '26 January 2017', 'b', 'The 101st Amendment introduced GST.'],
            ['Economy', 'The headquarters of the Reserve Bank of India is in:', 'New Delhi', 'Kolkata', 'Mumbai', 'Chennai', 'c', 'It was moved from Kolkata to Mumbai in 1937.'],
            ['Economy', 'NITI Aayog replaced which body?', 'Finance Commission', 'Planning Commission', 'Election Commission', 'UPSC', 'b', 'It was formed on 1 January 2015.'],
            ['Economy', 'Which is the national currency symbol introduced in 2010?', '$', '₹', '£', '€', 'b', 'The Rupee symbol was designed by D. Udaya Kumar.'],
            ['Sports', 'How many players are there in a cricket team on the field?', '9', '10', '11', '12', 'c', 'Eleven players per side.'],
            ['Sports', 'The Olympic Games are held every:', '2 years', '3 years', '4 years', '5 years', 'c', 'Summer Olympics are held every four years.'],
            ['Sports', 'Who was the first Indian to win an individual Olympic gold medal?', 'Neeraj Chopra', 'Abhinav Bindra', 'Rajyavardhan Rathore', 'Sushil Kumar', 'b', 'Abhinav Bindra won 10m air rifle gold in 2008.'],
            ['Sports', 'The term "Love" is used in which sport?', 'Cricket', 'Tennis', 'Hockey', 'Football', 'b', 'Love means a score of zero in tennis.'],
            ['Sports', 'The FIFA World Cup 2022 was held in:', 'Russia', 'Qatar', 'Brazil', 'Japan', 'b', 'Argentina won the final.'],
            ['Awards', 'The Nobel Peace Prize is awarded in which city?', 'Stockholm', 'Oslo', 'Geneva', 'Copenhagen', 'b', 'Other Nobel prizes are given in Stockholm.'],
            ['Awards', 'Bharat Ratna is the highest:', 'Sports award', 'Civilian award', 'Gallantry award', 'Literary award', 'b', 'It was instituted in 1954.'],
            ['Awards', 'Param Vir Chakra is awarded for:', 'Bravery in peace', 'Highest gallantry in war', 'Sports', 'Science', 'b', 'It is India\'s highest wartime military decoration.'],
            ['Awards', 'Who won the Nobel Prize in Literature in 1913 from India?', 'R. K. Narayan', 'Rabindranath Tagore', 'Premchand', 'Sarojini Naidu', 'b', 'For Gitanjali.'],
            ['Static GK', 'The capital of Australia is:', 'Sydney', 'Melbourne', 'Canberra', 'Perth', 'c', 'Canberra was chosen as a compromise between Sydney and Melbourne.'],
            ['Static GK', 'Which is the smallest country in the world by area?', 'Monaco', 'Vatican City', 'San Marino', 'Malta', 'b', 'About 0.44 sq km.'],
            ['Static GK', 'The currency of Japan is:', 'Yuan', 'Won', 'Yen', 'Ringgit', 'c', 'The yen is also used as an international reserve.'],
            ['Static GK', 'Which is the largest ocean in the world?', 'Atlantic', 'Indian', 'Arctic', 'Pacific', 'd', 'It covers about a third of the Earth\'s surface.'],
            ['Static GK', 'The national animal of India is:', 'Lion', 'Tiger', 'Elephant', 'Leopard', 'b', 'The Bengal tiger.'],
            ['Static GK', 'Taj Mahal was built by Shah Jahan in memory of:', 'Nur Jahan', 'Mumtaz Mahal', 'Jodha Bai', 'Razia Sultan', 'b', 'Construction ended around 1653.'],
            ['Static GK', 'Which city is known as the Pink City of India?', 'Jodhpur', 'Udaipur', 'Jaipur', 'Jaisalmer', 'c', 'Jaipur\'s old city was painted pink in 1876.'],
            ['Static GK', 'The Headquarters of the United Nations is in:', 'Geneva', 'London', 'Paris', 'New York', 'd', 'It is on the East River in Manhattan.'],
            ['Computer', 'What does CPU stand for?', 'Central Processing Unit', 'Computer Personal Unit', 'Central Program Utility', 'Control Processing Unit', 'a', 'The CPU is the brain of a computer.'],
            ['Computer', 'Which of these is an input device?', 'Monitor', 'Printer', 'Keyboard', 'Speaker', 'c', 'A keyboard sends data into the computer.'],
            ['Computer', 'HTML is used for:', 'Making web pages', 'Editing photos', 'Storing data', 'Playing music', 'a', 'HyperText Markup Language.'],
            ['Current Affairs', 'Chandrayaan-3 landed near which part of the Moon in 2023?', 'North pole', 'South pole', 'Equator', 'Far side', 'b', 'India became the first country to land near the lunar south pole.'],
            ['Current Affairs', 'The G20 summit 2023 was hosted by:', 'Indonesia', 'India', 'Brazil', 'Italy', 'b', 'It was held in New Delhi in September 2023.'],
            ['Current Affairs', 'Which country hosted the 2024 Summer Olympics?', 'Japan', 'USA', 'France', 'Australia', 'c', 'Paris hosted the games.'],
            ['Current Affairs', 'India\'s first human spaceflight programme by ISRO is called:', 'Mangalyaan', 'Gaganyaan', 'Aditya', 'Chandrayaan', 'b', 'Gaganyaan is India\'s human spaceflight programme.'],
            ['Reasoning', 'Find the next number: 2, 4, 8, 16, ?', '24', '30', '32', '36', 'c', 'Each number is doubled.'],
            ['Reasoning', 'If A = 1, B = 2, then what is the value of Z?', '24', '25', '26', '27', 'c', 'Z is the 26th letter.'],
            ['Maths', 'What is 15% of 200?', '20', '25', '30', '35', 'c', '200 × 15 / 100 = 30.'],
            ['Maths', 'The square root of 144 is:', '11', '12', '13', '14', 'b', '12 × 12 = 144.'],
            ['Maths', 'How many days are there in a leap year?', '364', '365', '366', '367', 'c', 'February has 29 days.'],
        ];

        foreach ($rows as [$cat, $q, $a, $b, $c, $d, $correct, $why]) {
            GkQuestion::firstOrCreate(['question' => $q], [
                'category' => $cat, 'option_a' => $a, 'option_b' => $b, 'option_c' => $c, 'option_d' => $d,
                'correct' => $correct, 'explanation' => $why, 'is_active' => true,
            ]);
        }
    }
}
