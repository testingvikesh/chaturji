<?php

declare(strict_types=1);

namespace App\Support;

final class PaperLanguage
{
    public static function detect(string ...$texts): string
    {
        $blob = mb_strtolower(implode("\n", $texts), 'UTF-8');
        if ($blob === '') {
            return 'en';
        }

        $gu = preg_match_all('/[\x{0A80}-\x{0AFF}]/u', $blob) ?: 0;
        $hi = preg_match_all('/[\x{0900}-\x{097F}]/u', $blob) ?: 0;
        $en = preg_match_all('/[a-z]/i', $blob) ?: 0;

        if (preg_match('/\b(gujarati|ગુજરાતી)\b/u', $blob)) {
            return 'gu';
        }
        if (preg_match('/\b(hindi|हिंदी|हिन्दी)\b/u', $blob)) {
            return 'hi';
        }

        if ($gu > 0 && $gu >= $hi && $gu * 2 >= max(1, (int) ($en / 3))) {
            return 'gu';
        }
        if ($hi > 0 && $hi > $gu && $hi * 2 >= max(1, (int) ($en / 3))) {
            return 'hi';
        }

        return 'en';
    }

    /** @return array<string, string> */
    public static function labels(string $lang): array
    {
        $lang = self::normalize($lang);
        $all = self::pack();

        return $all[$lang] ?? $all['en'];
    }

    public static function normalize(string $lang): string
    {
        $lang = strtolower(trim($lang));
        if (in_array($lang, ['gu', 'gujarati', 'guj'], true)) {
            return 'gu';
        }
        if (in_array($lang, ['hi', 'hindi', 'hin'], true)) {
            return 'hi';
        }

        return 'en';
    }

    public static function openaiLanguageName(string $lang): string
    {
        return match (self::normalize($lang)) {
            'gu' => 'Gujarati',
            'hi' => 'Hindi',
            default => 'English',
        };
    }

    public static function appreciation(string $lang, float $percent): string
    {
        $L = self::labels($lang);
        if ($percent >= 90) {
            return $L['apprec_excellent'];
        }
        if ($percent >= 75) {
            return $L['apprec_great'];
        }
        if ($percent >= 60) {
            return $L['apprec_good'];
        }
        if ($percent >= 40) {
            return $L['apprec_ok'];
        }

        return $L['apprec_keep'];
    }

    /**
     * @return array{emoji: string, message: string}
     */
    public static function appreciationPair(string $lang, int $percent): array
    {
        $message = self::appreciation($lang, $percent);
        $emoji = match (true) {
            $percent >= 90 => '🏆',
            $percent >= 75 => '🎉',
            $percent >= 50 => '👍',
            default => '💪',
        };

        return ['emoji' => $emoji, 'message' => $message];
    }

    public static function defaultComment(string $lang, float $ratio, bool $isCorrect): string
    {
        $L = self::labels($lang);
        if ($isCorrect || $ratio >= 0.99) {
            return $L['keep_good'];
        }
        if ($ratio >= 0.75) {
            return $L['almost'];
        }
        if ($ratio >= 0.4) {
            return $L['partial'];
        }

        return $L['need_more'];
    }

    public static function marksSidebarNote(string $lang, bool $isCorrect): string
    {
        $L = self::labels($lang);

        return $isCorrect ? $L['good_attempt'] : $L['incomplete_sidebar'];
    }

    public static function cleanTeacherComment(string $comment, string $lang, int $awarded, int $max): string
    {
        $comment = trim(preg_replace('/\s+/u', ' ', $comment) ?? $comment);
        $mixed = $comment !== ''
            && preg_match('/\p{Gujarati}|\p{Devanagari}/u', $comment) === 1
            && preg_match('/[A-Za-z]{5,}/', $comment) === 1;

        if ($comment !== '' && ! $mixed) {
            return $comment;
        }

        if ($lang === 'gu') {
            if ($max > 0 && $awarded >= $max) {
                return 'ઉત્તમ જવાબ. મુખ્ય વિચાર સ્પષ્ટ છે. :)';
            }
            if ($awarded > 0) {
                return 'સારો પ્રયત્ન. પૂરા ગુણ માટે થોડું વધુ વિવરણ ઉમેરો. :)';
            }

            return 'સારો પ્રયાસ. પાઠના મુખ્ય મુદ્દા ફરી લખો. :)';
        }

        if ($lang === 'hi') {
            if ($max > 0 && $awarded >= $max) {
                return 'उत्तम उत्तर। मुख्य बात साफ है। :)';
            }
            if ($awarded > 0) {
                return 'अच्छा प्रयास। पूरे अंक के लिए थोड़ा और विवरण लिखें। :)';
            }

            return 'अच्छा प्रयास। पाठ के मुख्य बिंदु फिर से लिखें। :)';
        }

        if ($max > 0 && $awarded >= $max) {
            return 'Excellent answer. The main idea is clear. :)';
        }
        if ($awarded > 0) {
            return 'Good effort. Add a little more detail for full marks. :)';
        }

        return 'Good try. Write the main points from the lesson again. :)';
    }

    public static function answerHeading(string $lang, int $number): string
    {
        $L = self::labels($lang);
        $label = trim($L['answer_no_label']);

        return '[ '.$label.' '.$number.' ]';
    }

    /** @return array<string, array<string, string>> */
    private static function pack(): array
    {
        return array (
  'gu' => 
  array (
    'teacher_checked_sheet' => 'તપાસેલી જવાબવહી',
    'marks' => 'ગુણ',
    'answer_no' => 'જવાબ',
    'answer_no_label' => 'જવાબ નં.',
    'correct' => 'સાચું',
    'incorrect' => 'ખોટું',
    'missing_points' => 'ખૂટતા મુદ્દા',
    'key_points' => 'મુખ્ય મુદ્દા',
    'tip' => 'સૂચન',
    'teachers_comment' => 'માસ્તરની ટિપ્પણી',
    'total_marks' => 'કુલ ગુણ',
    'percentage' => 'ટકા',
    'grade' => 'ગ્રેડ',
    'checked_by' => 'તપાસનાર',
    'ai_teacher' => 'AI Teacher',
    'date' => 'તારીખ',
    'correct_answer' => 'સાચો જવાબ',
    'q_marks' => 'પ્ર. ગુણ',
    'keep_good' => 'સારું કામ ચાલુ રાખો! :)',
    'covers_main' => 'જવાબમાં મુખ્ય વિચાર સારો છે.',
    'add_more' => 'પૂરા ગુણ મેળવવા થોડું વિવરણ ઉમેરો.',
    'almost' => 'લગભગ સાચું — થોડું સુધારો.',
    'good_attempt' => 'સારો પ્રયત્ન! મૂળ વિચાર સ્પષ્ટ છે.',
    'partial' => 'આંશિક સાચું. ખૂટતા મુદ્દા ઉમેરો.',
    'need_more' => 'વધુ સમજૂતિ જરૂરી છે. પાઠ ફરી વાંચો.',
    'rev_chapter' => 'પાઠ ફરી વાંચો અને ફરી પ્રયત્ન કરો.',
    'tip_incomplete' => 'જવાબ ટૂંકો છે. મુખ્ય મુદ્દા ઉમેરો.',
    'incomplete_sidebar' => 'સારો પ્રયત્ન. વધુ મુદ્દા ઉમેરો.',
    'point_main_idea' => 'મુખ્ય વિચાર સ્પષ્ટ છે',
    'point_clear_lang' => 'ભાષા સ્પષ્ટ છે',
    'point_write_complete' => 'પૂરો અર્થ લખો',
    'point_add_keyword' => 'પાઠનો મુખ્ય શબ્દ ઉમેરો',
    'point_add_example' => 'એક ઉદાહરણ ઉમેરો',
    'point_more_detail' => 'વધુ વિસ્તારથી લખો',
    'correct_idea' => 'સાચો વિચાર',
    'no_answer' => 'જવાબ મળ્યો નથી.',
    'write_clearly' => 'જવાબ સ્પષ્ટ લખો.',
    'answer_not_written' => 'જવાબ લખાયેલો નથી',
    'obj_correct' => 'સરસ! તમારો જવાબ સાચો છે.',
    'obj_wrong' => 'ફરી પ્રયત્ન કરો. સાચો જવાબ જુઓ.',
    'obj_comment_ok' => 'બરાબર!',
    'obj_comment_bad' => 'વધુ વિસ્તાર લખો. શીખતા રહો :)',
    'local_ok_fb' => 'શાનદાર જવાબ! તમે મુદ્દો સારી રીતે સમજ્યા. :)',
    'local_bad_fb' => 'સારો પ્રયત્ન! સાચો જવાબ ધ્યાનથી જુઓ. :)',
    'local_ok_tc' => 'સાચું! શાબાશ. શીખતા રહો :)',
    'local_bad_tc' => 'વધુ વિસ્તાર લખો. પાઠનો મુખ્ય વિચાર યાદ રાખો. :)',
    'apprec_excellent' => 'શાનદાર! તમે ખૂબ સારું કામ કર્યું છે. 🏆',
    'apprec_great' => 'શાબાશ! ખૂબ સારું પરિણામ. 🙂',
    'apprec_good' => 'સારું પ્રયત્ન! અભ્યાસ ચાલુ રાખો. 👍',
    'apprec_ok' => 'સારો પ્રયત્ન! થોડું અભ્યાસ કરો. 🙂',
    'apprec_keep' => 'અભ્યાસ ચાલુ રાખો — તમે પાર પડશે. 🙂',
    'ui_check_failed' => 'જવાબ તપાસ નિષ્ફળ થઈ',
    'ui_checked_n' => 'શિક્ષકે :n લખેલ જવાબ તપાસ્યા',
    'ui_paper_total' => '(:paper માં :total પ્રશ્ન છે)',
    'ui_score' => 'ગુણ',
    'ui_facility' => 'પેપર તપાસ સુવિધા',
    'ui_sheet_title' => 'તપાસેલી જવાબવહી',
    'ui_sheet_desc' => 'ગુણ, મુખ્ય મુદ્દા અને માસ્તર ટિપ્પણી સાથે',
    'ui_open_sheet' => 'તપાસેલ જવાબવહી ખોલો / ડાઉનલોડ કરો',
    'ui_question_wise' => 'પ્રશ્ન પ્રમાણે પરિણામ',
    'ui_your_answer' => 'તમારો જવાબ',
    'ui_edited_missing' => 'આ પ્રયાસ માટે સંપાદિત છબી સાચવાઈ નથી.',
    'ui_view_upload' => 'અપલોડ થયેલ પેપર જુઓ',
  ),
  'hi' => 
  array (
    'teacher_checked_sheet' => 'शिक्षक जाँची हुई उत्तर पत्रिका',
    'marks' => 'अंक',
    'answer_no' => 'उत्तर',
    'answer_no_label' => 'उत्तर - क्रम',
    'correct' => 'सही',
    'incorrect' => 'गलत',
    'missing_points' => 'गायब बिंदु',
    'key_points' => 'मुख्य बिंदु',
    'tip' => 'Tip',
    'teachers_comment' => 'शिक्षक टिप्पणी',
    'total_marks' => 'कुल अंक',
    'percentage' => 'प्रतिशत',
    'grade' => 'ग्रेड',
    'checked_by' => 'जाँचकर्ता',
    'ai_teacher' => 'AI Teacher',
    'date' => 'तारीख',
    'correct_answer' => 'सही उत्तर',
    'q_marks' => 'प्रश्न अंक',
    'keep_good' => 'अच्छा काम जारी रखो! :)',
    'covers_main' => 'उत्तर मुख्य विचार को अच्छी तरह समझता है.',
    'add_more' => 'पूरे अंक पाने के लिए कुछ विवरण जोड़ें.',
    'almost' => 'लगभग सही — थोड़ा सुधारो.',
    'good_attempt' => 'अच्छा प्रयास. मूल विचार स्पष्ट है. आगे बढे!',
    'partial' => 'आंशिक सही. गायब बिंदु जोड़ें.',
    'need_more' => 'अधिक विवरण जरूरी है. अध्याय फिर से पढ़ें.',
    'rev_chapter' => 'अध्याय फिर से पढ़ें और फिर से प्रयास करें.',
    'tip_incomplete' => 'उत्तर बहुत छोटा है और महत्वपूर्ण बिंदु गायब हैं.',
    'incomplete_sidebar' => 'अच्छा प्रयास. विचार अपूरा है. अधिक बिंदु और उदाहरण जोड़ें.',
    'point_main_idea' => 'मुख्य विचार स्पष्ट है',
    'point_clear_lang' => 'भाषा समझ आती है',
    'point_write_complete' => 'पूरा अर्थ लिखें',
    'point_add_keyword' => 'पाठ का मुख्य शब्द जोड़ें',
    'point_add_example' => 'एक उपयुक्त उदाहरण जोड़ें',
    'point_more_detail' => 'अधिक विस्तार से उत्तर लिखें',
    'correct_idea' => 'सही विचार',
    'no_answer' => 'उत्तर नहीं मिला.',
    'write_clearly' => 'उत्तर स्पष्ट लिखें.',
    'answer_not_written' => 'उत्तर लिखा नहीं',
    'obj_correct' => 'शाबाश! आपका उत्तर सही है.',
    'obj_wrong' => 'फिर प्रयास करें. सही उत्तर देखें और समझें.',
    'obj_comment_ok' => 'जबाब!',
    'obj_comment_bad' => 'अधिक विस्तार लिखें. सीखते रहें :)',
    'local_ok_fb' => 'शानदार उत्तर! आपने बात सपष्ट समझमी. आगे बढे! :)',
    'local_bad_fb' => 'अच्छा प्रयास! सही उत्तर ध्यान से पढ़ें और फिर अभ्यास करें. :)',
    'local_ok_tc' => 'सही! शाबाश. सीखते रहें :)',
    'local_bad_tc' => 'अधिक विस्तार लिखें. अध्याय का मुख्य विचार याद रखें. :)',
    'apprec_excellent' => 'शानदार! आपने बहुत अच्छा काम किया है. 🏆',
    'apprec_great' => 'शाबाश! बहुत अच्छा परिणाम. 🙂',
    'apprec_good' => 'अच्छा प्रयत्न! अभ्यास जारी रखो. 👍',
    'apprec_ok' => 'अच्छा प्रयास! थोड़ा अभ्यास करो. 🙂',
    'apprec_keep' => 'अभ्यास जारी रखो — आप कर सकते हैं. 🙂',
    'ui_check_failed' => 'उत्तर जाँच विफल रही',
    'ui_checked_n' => 'शिक्षक ने :n लिखित उत्तर(तों) जाँचे',
    'ui_paper_total' => '(:paper में :total प्रश्न हैं)',
    'ui_score' => 'स्कोर',
    'ui_facility' => 'पेपर जाँच सुविधा',
    'ui_sheet_title' => 'शिक्षक जाँची उत्तर पत्रिका',
    'ui_sheet_desc' => 'अंक, गायब बिंदु और शिक्षक टिप्पणी के साथ',
    'ui_open_sheet' => 'जाँची हुई पत्रिका खोलें / डाउनलोड करें',
    'ui_question_wise' => 'प्रश्नवार परिणाम',
    'ui_your_answer' => 'आपका उत्तर',
    'ui_edited_missing' => 'इस प्रयास के लिए संपादित छवि सहेजी नहीं.',
    'ui_view_upload' => 'अपलोड हुई पत्रिका देखें',
  ),
  'en' => 
  array (
    'teacher_checked_sheet' => 'Teacher Checked Sheet',
    'marks' => 'Marks',
    'answer_no' => 'Answer',
    'answer_no_label' => 'Answer - No',
    'correct' => 'Correct',
    'incorrect' => 'Incorrect',
    'missing_points' => 'Missing key points',
    'key_points' => 'Key points covered',
    'tip' => 'Tip',
    'teachers_comment' => 'Teacher\'s Comment',
    'total_marks' => 'Total Marks',
    'percentage' => 'Percentage',
    'grade' => 'Grade',
    'checked_by' => 'Checked by',
    'ai_teacher' => 'AI Teacher',
    'date' => 'Date',
    'correct_answer' => 'Correct answer',
    'q_marks' => 'Q. Marks',
    'keep_good' => 'Excellent work! Your answer is clear and matches the main idea of the lesson. Keep writing with the same care. Well done! :)',
    'covers_main' => 'Good answer. Main idea is clear and complete.',
    'add_more' => 'Add a little more detail and one example to earn full marks.',
    'almost' => 'Almost correct! The main idea is there. Add one more supporting point and check your spelling. Keep going! :)',
    'good_attempt' => 'Good attempt. The basic idea is clear. Write one more full sentence next time.',
    'partial' => 'Partially correct. You started well — now add the missing key points from the chapter. You can improve! :)',
    'need_more' => 'Good try, but more explanation is needed. Revise the chapter and rewrite the full answer in clear English. Keep learning! :)',
    'rev_chapter' => 'Please revise the chapter and try again with full sentences.',
    'tip_incomplete' => 'Answer is incomplete. Add key points, keywords, and one example.',
    'incomplete_sidebar' => 'Good attempt, but the idea is incomplete. Add more key points, keywords from the lesson, and one clear example.',
    'point_main_idea' => 'Main idea of the lesson is explained clearly',
    'point_clear_lang' => 'Answer is written in clear, simple English',
    'point_write_complete' => 'Write the complete meaning in full sentences',
    'point_add_keyword' => 'Include an important keyword from the chapter',
    'point_add_example' => 'Add one suitable real-life example',
    'point_more_detail' => 'Explain the answer with more detail',
    'point_link_lesson' => 'Connect the answer to the lesson topic',
    'point_neat_writing' => 'Keep handwriting neat and easy to read',
    'correct_idea' => 'Correct idea',
    'no_answer' => 'No answer was found on the sheet.',
    'write_clearly' => 'Please write your answer clearly in English. :)',
    'answer_not_written' => 'Answer was not written',
    'obj_correct' => 'Well done! Your answer is correct and clear.',
    'obj_wrong' => 'Not quite right. Read the correct answer carefully and try again.',
    'obj_comment_ok' => 'Correct answer! Nice work. Keep learning! :)',
    'obj_comment_bad' => 'Close, but not complete. Write the full idea in more detail. Keep learning! :)',
    'local_ok_fb' => 'Excellent answer! You understood the point clearly and wrote it well. Keep it up! :)',
    'local_bad_fb' => 'Good try! Review the correct answer carefully, note the key points, and practice again. You will improve! :)',
    'local_ok_tc' => 'Correct and clear! You covered the main idea well. Keep learning! :)',
    'local_bad_tc' => 'Write in more detail. Remember the key idea from the chapter and add one example. Keep learning! :)',
    'apprec_excellent' => 'Outstanding! You did excellent work. 🏆',
    'apprec_great' => 'Awesome! Very good performance. 🙂',
    'apprec_good' => 'Good effort! Keep practicing. 👍',
    'apprec_ok' => 'Nice try! Practice a little more. 🙂',
    'apprec_keep' => 'Keep practicing — you can do it. 🙂',
    'ui_check_failed' => 'Answer check failed',
    'ui_checked_n' => 'Teacher checked :n written answer(s)',
    'ui_paper_total' => '(:paper has :total total questions)',
    'ui_score' => 'Score',
    'ui_facility' => 'Paper Check Facility',
    'ui_sheet_title' => 'Teacher Checked Answer Sheet',
    'ui_sheet_desc' => 'Notebook format with marks, missing key points & teacher comment',
    'ui_open_sheet' => 'Open / download edited checked sheet (red pen marks)',
    'ui_question_wise' => 'Question-wise Results',
    'ui_your_answer' => 'Your answer',
    'ui_edited_missing' => 'Edited image not saved for this attempt.',
    'ui_view_upload' => 'View uploaded sheet',
  ),
);
    }
}
