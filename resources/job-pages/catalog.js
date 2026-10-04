/* كتالوج الحقول والأقسام — مشترك بين الصفحات والأداة */
var GOVS = ['مسقط','ظفار','مسندم','البريمي','الداخلية','شمال الباطنة','جنوب الباطنة','شمال الشرقية','جنوب الشرقية','الظاهرة','الوسطى'];

var FIELDS = {
  nationality:{label:'الجنسية',type:'radio',options:['عُماني','غير عُماني'],group:'هوية'},
  dob:{label:'تاريخ الميلاد',type:'date',group:'هوية'},
  name:{label:'الاسم الثلاثي',type:'text',auto:'name',group:'هوية'},
  nid:{label:'رقم البطاقة الشخصية',type:'text',inputmode:'numeric',pattern:'^\\d{6,10}$',patternMsg:'أدخل رقم البطاقة أرقامًا فقط.',group:'هوية'},
  whatsapp:{label:'رقم الواتساب',type:'tel',inputmode:'tel',auto:'tel',pattern:'^(\\+?968)?\\s?\\d{8}$',patternMsg:'أدخل رقمًا عُمانيًّا من ٨ أرقام.',hint:'مثال: 91234567',group:'تواصل'},
  email:{label:'البريد الإلكتروني',type:'email',auto:'email',pattern:'^[^\\s@]+@[^\\s@]+\\.[^\\s@]+$',patternMsg:'صيغة البريد غير صحيحة.',group:'تواصل'},
  gov:{label:'المحافظة',type:'select',options:GOVS,group:'تواصل'},
  wilayat:{label:'الولاية',type:'text',group:'تواصل'},
  edu:{label:'المؤهل العلمي',type:'select',options:['ثانوية عامة','دبلوم','بكالوريوس','ماجستير','دكتوراه'],group:'مؤهلات'},
  major:{label:'التخصص',type:'text',group:'مؤهلات'},
  exp:{label:'سنوات الخبرة في المجال',type:'select',options:['لا خبرة','أقل من سنة','١–٣ سنوات','٤–٦ سنوات','٧–١٠ سنوات','أكثر من ١٠ سنوات'],group:'مؤهلات'},
  employer:{label:'جهة العمل الحالية أو الأخيرة',type:'text',group:'مؤهلات'},
  hifz:{label:'هل تحفظ القرآن الكريم كاملًا من الفاتحة إلى الناس؟',type:'radio',options:['نعم','لا'],group:'قرآن'},
  prior:{label:'هل التحقتَ ببرنامجٍ قرآنيٍّ من قبل؟',type:'radio',options:['نعم — برنامج «مكنون»','نعم — برنامج آخر','لا'],
         reveal:{k:'prior_other',label:'حدِّد البرنامج',when:['نعم — برنامج آخر']},
         hint:'لا يُبنى على هذا قبولٌ ولا ردّ — غايتُه أن نعرف من أين جاءت خبرتُك.',group:'قرآن'},
  branch:{label:'الفرع المفضّل',type:'select',options:[],fromBranches:true,group:'وظيفة'},
  english:{label:'مستوى اللغة الإنجليزية',type:'select',options:['IELTS','TOEFL','شهادة جامعية بالإنجليزية','لا أحمل شهادة'],
           reveal:{k:'english_score',label:'الدرجة أو المستوى',not:['لا أحمل شهادة']},group:'مؤهلات'},
  pay:{label:'الأجر الشهري المتوقع',type:'number',unit:'ر.ع',min:0,hint:'رقم بالريال العماني',group:'وظيفة'},
  cv:{label:'السيرة الذاتية',type:'file',accept:'.pdf,.doc,.docx',hint:'PDF أو Word — حتى ٥ ميجابايت',group:'مرفقات'},
  certs:{label:'الشهادات والمؤهلات',type:'file',accept:'.pdf,.jpg,.jpeg,.png',multiple:true,hint:'PDF أو صور — حتى ٥ ميجابايت للملف',group:'مرفقات'},
  portfolio:{label:'رابط أعمالك',type:'url',hint:'موقع، حساب، أو ملف على الإنترنت',group:'مرفقات'},
  cover:{label:'نبذة عنك ولماذا هذه الوظيفة؟',type:'textarea',max:600,hint:'بضعة أسطر تكفي',group:'أسئلة'},
  essay:{label:'سؤال الوظيفة',type:'textarea',max:1200,hint:'اكتب بما تراه عمليًّا، لا بما تراه مثاليًّا',group:'أسئلة'},
  ref1:{label:'مُزكٍّ أول (الاسم والهاتف والصلة)',type:'text',group:'مزكّون'},
  ref2:{label:'مُزكٍّ ثانٍ (الاسم والهاتف والصلة)',type:'text',group:'مزكّون'}
};

var BLOCKS = [
  {k:'about',t:'عن الوظيفة',kind:'text'},
  {k:'tasks',t:'ماذا ستفعل؟',kind:'lines'},
  {k:'who',t:'من نبحث عنه؟',kind:'who'},
  {k:'schedule',t:'الوقت والالتزام',kind:'pairs'},
  {k:'branches',t:'الفروع والمقاعد المتاحة',kind:'pairs'},
  {k:'pay',t:'الأجر',kind:'text'},
  {k:'growth',t:'مسار الترقّي',kind:'lines'},
  {k:'kpis',t:'كيف يُقاس نجاحك؟',kind:'lines'},
  {k:'values',t:'خماسية السكينة في هذه الوظيفة',kind:'values'},
  {k:'process',t:'ماذا بعد التقديم؟',kind:'pairs'}
];

/* أعمدة الخماسية — كما وردت في لوحة «قيم خماسية السكينة» */
var PILLARS = [
  {id:'ibada',n:'عبادة',c:'#2E7D32',s:['الإيمان','الإحسان','قول الحسن','الصلاة','الإنفاق']},
  {id:'ilm',n:'علم',c:'#B27800',s:['القرآن','البيان','دراسة العلوم','القراءة','الكتابة']},
  {id:'amal',n:'عمل',c:'#1F5FBF',s:['الزراعة','العمل المنزلي','التجارة','الادخار والتثمير','العمل التطوعي']},
  {id:'lab',n:'لعب',c:'#C2255C',s:['الرتع','اللعب بالدمى والمجسمات','الألعاب الحركية','الألعاب التقنية','الألعاب التمثيلية']},
  {id:'nawm',n:'نوم وصحة',c:'#D9650B',s:['ماذا يفعل قبل النوم؟','النوم','ما بعد الاستيقاظ','الغذاء','الصحة']}
];
