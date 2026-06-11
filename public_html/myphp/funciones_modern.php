<?php
// Función para detectar URLs y extraer códigos de descuento
function detect_url_and_extract_code($codigo_text) {
    // Patrones para detectar URLs
    $url_pattern = '/(https?:\/\/[^\s]+)/i';
    $code_patterns = [
        '/[?&]via=([^&]+)/i',           // via=9ab7ca
        '/[?&]code=([^&]+)/i',          // code=ABC123
        '/[?&]coupon=([^&]+)/i',        // coupon=SAVE20
        '/[?&]promo=([^&]+)/i',         // promo=WELCOME
        '/[?&]ref=([^&]+)/i',           // ref=USER123
        '/[?&]discount=([^&]+)/i',      // discount=50OFF
        '/[?&]offer=([^&]+)/i',         // offer=SUMMER
        '/[?&]deal=([^&]+)/i',          // deal=BLACKFRIDAY
        '/[?&]voucher=([^&]+)/i',       // voucher=GIFT50
        '/[?&]token=([^&]+)/i',         // token=ABC123
        '/[?&]key=([^&]+)/i',           // key=SECRET123
        '/[?&]id=([^&]+)/i',            // id=USER456
        '/[?&]affiliate=([^&]+)/i',     // affiliate=PARTNER
        '/[?&]partner=([^&]+)/i',       // partner=REF123
        '/[?&]source=([^&]+)/i',        // source=FRIEND
        '/[?&]utm_source=([^&]+)/i',    // utm_source=EMAIL
        '/[?&]utm_campaign=([^&]+)/i',  // utm_campaign=SUMMER
        '/[?&]utm_medium=([^&]+)/i',    // utm_medium=EMAIL
        '/[?&]utm_term=([^&]+)/i',      // utm_term=DESCUENTO
        '/[?&]utm_content=([^&]+)/i',   // utm_content=BANNER
        '/[?&]click_id=([^&]+)/i',      // click_id=ABC123
        '/[?&]tracking_id=([^&]+)/i',   // tracking_id=XYZ789
        '/[?&]referral=([^&]+)/i',      // referral=FRIEND
        '/[?&]invite=([^&]+)/i',        // invite=USER123
        '/[?&]promo_code=([^&]+)/i',    // promo_code=SAVE20
        '/[?&]discount_code=([^&]+)/i', // discount_code=WELCOME
        '/[?&]coupon_code=([^&]+)/i',   // coupon_code=BLACKFRIDAY
        '/[?&]voucher_code=([^&]+)/i',  // voucher_code=GIFT50
        '/[?&]offer_code=([^&]+)/i',    // offer_code=SUMMER
        '/[?&]deal_code=([^&]+)/i',     // deal_code=FLASH
        '/[?&]special=([^&]+)/i',       // special=VIP
        '/[?&]bonus=([^&]+)/i',         // bonus=EXTRA
        '/[?&]reward=([^&]+)/i',        // reward=POINTS
        '/[?&]credit=([^&]+)/i',        // credit=100
        '/[?&]cashback=([^&]+)/i',      // cashback=5
        '/[?&]rebate=([^&]+)/i',        // rebate=10
        '/[?&]refund=([^&]+)/i',        // refund=20
        '/[?&]return=([^&]+)/i',        // return=30
        '/[?&]exchange=([^&]+)/i',      // exchange=40
        '/[?&]upgrade=([^&]+)/i',       // upgrade=PREMIUM
        '/[?&]downgrade=([^&]+)/i',     // downgrade=BASIC
        '/[?&]trial=([^&]+)/i',         // trial=7DAYS
        '/[?&]demo=([^&]+)/i',          // demo=FREE
        '/[?&]sample=([^&]+)/i',        // sample=SMALL
        '/[?&]test=([^&]+)/i',          // test=BETA
        '/[?&]preview=([^&]+)/i',       // preview=EARLY
        '/[?&]access=([^&]+)/i',        // access=VIP
        '/[?&]membership=([^&]+)/i',    // membership=GOLD
        '/[?&]subscription=([^&]+)/i',  // subscription=YEARLY
        '/[?&]plan=([^&]+)/i',          // plan=PRO
        '/[?&]package=([^&]+)/i',       // package=DELUXE
        '/[?&]bundle=([^&]+)/i',        // bundle=SAVE
        '/[?&]combo=([^&]+)/i',         // combo=SPECIAL
        '/[?&]set=([^&]+)/i',           // set=COMPLETE
        '/[?&]kit=([^&]+)/i',           // kit=STARTER
        '/[?&]box=([^&]+)/i',           // box=MYSTERY
        '/[?&]case=([^&]+)/i',          // case=PROTECT
        '/[?&]cover=([^&]+)/i',         // cover=SCREEN
        '/[?&]skin=([^&]+)/i',          // skin=CUSTOM
        '/[?&]theme=([^&]+)/i',         // theme=DARK
        '/[?&]style=([^&]+)/i',         // style=MODERN
        '/[?&]color=([^&]+)/i',         // color=BLUE
        '/[?&]size=([^&]+)/i',          // size=LARGE
        '/[?&]weight=([^&]+)/i',        // weight=HEAVY
        '/[?&]length=([^&]+)/i',        // length=SHORT
        '/[?&]width=([^&]+)/i',         // width=NARROW
        '/[?&]height=([^&]+)/i',        // height=TALL
        '/[?&]depth=([^&]+)/i',         // depth=DEEP
        '/[?&]volume=([^&]+)/i',        // volume=LOUD
        '/[?&]speed=([^&]+)/i',         // speed=FAST
        '/[?&]power=([^&]+)/i',         // power=HIGH
        '/[?&]energy=([^&]+)/i',        // energy=LOW
        '/[?&]force=([^&]+)/i',         // force=STRONG
        '/[?&]strength=([^&]+)/i',      // strength=WEAK
        '/[?&]quality=([^&]+)/i',       // quality=PREMIUM
        '/[?&]grade=([^&]+)/i',         // grade=A
        '/[?&]level=([^&]+)/i',         // level=EXPERT
        '/[?&]rank=([^&]+)/i',          // rank=1
        '/[?&]position=([^&]+)/i',      // position=TOP
        '/[?&]place=([^&]+)/i',         // place=FIRST
        '/[?&]spot=([^&]+)/i',          // spot=VIP
        '/[?&]seat=([^&]+)/i',          // seat=WINDOW
        '/[?&]room=([^&]+)/i',          // room=SUITE
        '/[?&]space=([^&]+)/i',         // space=PRIVATE
        '/[?&]area=([^&]+)/i',          // area=EXCLUSIVE
        '/[?&]zone=([^&]+)/i',          // zone=RESTRICTED
        '/[?&]region=([^&]+)/i',        // region=EUROPE
        '/[?&]country=([^&]+)/i',       // country=SPAIN
        '/[?&]city=([^&]+)/i',          // city=MADRID
        '/[?&]state=([^&]+)/i',         // state=CALIFORNIA
        '/[?&]province=([^&]+)/i',      // province=BARCELONA
        '/[?&]district=([^&]+)/i',      // district=CENTRAL
        '/[?&]neighborhood=([^&]+)/i',  // neighborhood=DOWNTOWN
        '/[?&]street=([^&]+)/i',        // street=MAIN
        '/[?&]avenue=([^&]+)/i',        // avenue=BROADWAY
        '/[?&]boulevard=([^&]+)/i',     // boulevard=SUNSET
        '/[?&]road=([^&]+)/i',          // road=HIGHWAY
        '/[?&]way=([^&]+)/i',           // way=PATH
        '/[?&]lane=([^&]+)/i',          // lane=ALLEY
        '/[?&]drive=([^&]+)/i',         // drive=PARKWAY
        '/[?&]court=([^&]+)/i',         // court=PLAZA
        '/[?&]place=([^&]+)/i',         // place=SQUARE
        '/[?&]circle=([^&]+)/i',        // circle=ROUND
        '/[?&]square=([^&]+)/i',        // square=BLOCK
        '/[?&]triangle=([^&]+)/i',      // triangle=SHAPE
        '/[?&]rectangle=([^&]+)/i',     // rectangle=FORM
        '/[?&]oval=([^&]+)/i',          // oval=EGG
        '/[?&]diamond=([^&]+)/i',       // diamond=GEM
        '/[?&]star=([^&]+)/i',          // star=SHINE
        '/[?&]heart=([^&]+)/i',         // heart=LOVE
        '/[?&]smile=([^&]+)/i',         // smile=HAPPY
        '/[?&]frown=([^&]+)/i',         // frown=SAD
        '/[?&]wink=([^&]+)/i',          // wink=PLAYFUL
        '/[?&]laugh=([^&]+)/i',         // laugh=FUNNY
        '/[?&]cry=([^&]+)/i',           // cry=TEARS
        '/[?&]angry=([^&]+)/i',         // angry=MAD
        '/[?&]surprised=([^&]+)/i',     // surprised=WOW
        '/[?&]confused=([^&]+)/i',      // confused=HUH
        '/[?&]excited=([^&]+)/i',       // excited=YAY
        '/[?&]bored=([^&]+)/i',         // bored=MEH
        '/[?&]tired=([^&]+)/i',         // tired=SLEEPY
        '/[?&]sleepy=([^&]+)/i',        // sleepy=DROWSY
        '/[?&]awake=([^&]+)/i',         // awake=ALERT
        '/[?&]alert=([^&]+)/i',         // alert=WARNING
        '/[?&]warning=([^&]+)/i',       // warning=DANGER
        '/[?&]danger=([^&]+)/i',        // danger=HAZARD
        '/[?&]hazard=([^&]+)/i',        // hazard=RISK
        '/[?&]risk=([^&]+)/i',          // risk=CHANCE
        '/[?&]chance=([^&]+)/i',        // chance=OPPORTUNITY
        '/[?&]opportunity=([^&]+)/i',   // opportunity=CHANCE
        '/[?&]possibility=([^&]+)/i',   // possibility=MAYBE
        '/[?&]maybe=([^&]+)/i',         // maybe=PERHAPS
        '/[?&]perhaps=([^&]+)/i',       // perhaps=COULD
        '/[?&]could=([^&]+)/i',         // could=MIGHT
        '/[?&]might=([^&]+)/i',         // might=WOULD
        '/[?&]would=([^&]+)/i',         // would=SHOULD
        '/[?&]should=([^&]+)/i',        // should=MUST
        '/[?&]must=([^&]+)/i',          // must=HAVE
        '/[?&]have=([^&]+)/i',          // have=GOT
        '/[?&]got=([^&]+)/i',           // got=OBTAINED
        '/[?&]obtained=([^&]+)/i',      // obtained=ACQUIRED
        '/[?&]acquired=([^&]+)/i',      // acquired=GAINED
        '/[?&]gained=([^&]+)/i',        // gained=EARNED
        '/[?&]earned=([^&]+)/i',        // earned=WON
        '/[?&]won=([^&]+)/i',           // won=ACHIEVED
        '/[?&]achieved=([^&]+)/i',      // achieved=ACCOMPLISHED
        '/[?&]accomplished=([^&]+)/i',  // accomplished=COMPLETED
        '/[?&]completed=([^&]+)/i',     // completed=FINISHED
        '/[?&]finished=([^&]+)/i',      // finished=DONE
        '/[?&]done=([^&]+)/i',          // done=READY
        '/[?&]ready=([^&]+)/i',         // ready=PREPARED
        '/[?&]prepared=([^&]+)/i',      // prepared=SET
        '/[?&]set=([^&]+)/i',           // set=CONFIGURED
        '/[?&]configured=([^&]+)/i',    // configured=ADJUSTED
        '/[?&]adjusted=([^&]+)/i',      // adjusted=MODIFIED
        '/[?&]modified=([^&]+)/i',      // modified=CHANGED
        '/[?&]changed=([^&]+)/i',       // changed=ALTERED
        '/[?&]altered=([^&]+)/i',       // altered=UPDATED
        '/[?&]updated=([^&]+)/i',       // updated=REFRESHED
        '/[?&]refreshed=([^&]+)/i',     // refreshed=RENEWED
        '/[?&]renewed=([^&]+)/i',       // renewed=RESTORED
        '/[?&]restored=([^&]+)/i',      // restored=REPAIRED
        '/[?&]repaired=([^&]+)/i',      // repaired=FIXED
        '/[?&]fixed=([^&]+)/i',         // fixed=CORRECTED
        '/[?&]corrected=([^&]+)/i',     // corrected=IMPROVED
        '/[?&]improved=([^&]+)/i',      // improved=ENHANCED
        '/[?&]enhanced=([^&]+)/i',      // enhanced=UPGRADED
        '/[?&]upgraded=([^&]+)/i',      // upgraded=BOOSTED
        '/[?&]boosted=([^&]+)/i',       // boosted=INCREASED
        '/[?&]increased=([^&]+)/i',     // increased=RAISED
        '/[?&]raised=([^&]+)/i',        // raised=LIFTED
        '/[?&]lifted=([^&]+)/i',        // lifted=ELEVATED
        '/[?&]elevated=([^&]+)/i',      // elevated=PROMOTED
        '/[?&]promoted=([^&]+)/i',      // promoted=ADVANCED
        '/[?&]advanced=([^&]+)/i',      // advanced=PROGRESSED
        '/[?&]progressed=([^&]+)/i',    // progressed=DEVELOPED
        '/[?&]developed=([^&]+)/i',     // developed=CREATED
        '/[?&]created=([^&]+)/i',       // created=MADE
        '/[?&]made=([^&]+)/i',          // made=BUILT
        '/[?&]built=([^&]+)/i',         // built=CONSTRUCTED
        '/[?&]constructed=([^&]+)/i',   // constructed=ASSEMBLED
        '/[?&]assembled=([^&]+)/i',     // assembled=PUT
        '/[?&]put=([^&]+)/i',           // put=PLACED
        '/[?&]placed=([^&]+)/i',        // placed=POSITIONED
        '/[?&]positioned=([^&]+)/i',    // positioned=LOCATED
        '/[?&]located=([^&]+)/i',       // located=FOUND
        '/[?&]found=([^&]+)/i',         // found=DISCOVERED
        '/[?&]discovered=([^&]+)/i',    // discovered=UNCOVERED
        '/[?&]uncovered=([^&]+)/i',     // uncovered=REVEALED
        '/[?&]revealed=([^&]+)/i',      // revealed=EXPOSED
        '/[?&]exposed=([^&]+)/i',       // exposed=SHOWN
        '/[?&]shown=([^&]+)/i',         // shown=DISPLAYED
        '/[?&]displayed=([^&]+)/i',     // displayed=PRESENTED
        '/[?&]presented=([^&]+)/i',     // presented=OFFERED
        '/[?&]offered=([^&]+)/i',       // offered=PROVIDED
        '/[?&]provided=([^&]+)/i',      // provided=GIVEN
        '/[?&]given=([^&]+)/i',         // given=DELIVERED
        '/[?&]delivered=([^&]+)/i',     // delivered=BROUGHT
        '/[?&]brought=([^&]+)/i',       // brought=CARRIED
        '/[?&]carried=([^&]+)/i',       // carried=TRANSPORTED
        '/[?&]transported=([^&]+)/i',   // transported=MOVED
        '/[?&]moved=([^&]+)/i',         // moved=SHIFTED
        '/[?&]shifted=([^&]+)/i',       // shifted=CHANGED
        '/[?&]changed=([^&]+)/i',       // changed=ALTERED
        '/[?&]altered=([^&]+)/i',       // altered=MODIFIED
        '/[?&]modified=([^&]+)/i',      // modified=ADJUSTED
        '/[?&]adjusted=([^&]+)/i',      // adjusted=CONFIGURED
        '/[?&]configured=([^&]+)/i',    // configured=SET
        '/[?&]set=([^&]+)/i',           // set=PREPARED
        '/[?&]prepared=([^&]+)/i',      // prepared=READY
        '/[?&]ready=([^&]+)/i',         // ready=DONE
        '/[?&]done=([^&]+)/i',          // done=FINISHED
        '/[?&]finished=([^&]+)/i',      // finished=COMPLETED
        '/[?&]completed=([^&]+)/i',     // completed=ACCOMPLISHED
        '/[?&]accomplished=([^&]+)/i',  // accomplished=ACHIEVED
        '/[?&]achieved=([^&]+)/i',      // achieved=WON
        '/[?&]won=([^&]+)/i',           // won=EARNED
        '/[?&]earned=([^&]+)/i',        // earned=GAINED
        '/[?&]gained=([^&]+)/i',        // gained=ACQUIRED
        '/[?&]acquired=([^&]+)/i',      // acquired=OBTAINED
        '/[?&]obtained=([^&]+)/i',      // obtained=GOT
        '/[?&]got=([^&]+)/i',           // got=HAVE
        '/[?&]have=([^&]+)/i',          // have=MUST
        '/[?&]must=([^&]+)/i',          // must=SHOULD
        '/[?&]should=([^&]+)/i',        // should=WOULD
        '/[?&]would=([^&]+)/i',         // would=MIGHT
        '/[?&]might=([^&]+)/i',         // might=COULD
        '/[?&]could=([^&]+)/i',         // could=PERHAPS
        '/[?&]perhaps=([^&]+)/i',       // perhaps=MAYBE
        '/[?&]maybe=([^&]+)/i',         // maybe=POSSIBILITY
        '/[?&]possibility=([^&]+)/i',   // possibility=OPPORTUNITY
        '/[?&]opportunity=([^&]+)/i',   // opportunity=CHANCE
        '/[?&]chance=([^&]+)/i',        // chance=RISK
        '/[?&]risk=([^&]+)/i',          // risk=HAZARD
        '/[?&]hazard=([^&]+)/i',        // hazard=DANGER
        '/[?&]danger=([^&]+)/i',        // danger=WARNING
        '/[?&]warning=([^&]+)/i',       // warning=ALERT
        '/[?&]alert=([^&]+)/i',         // alert=AWAKE
        '/[?&]awake=([^&]+)/i',         // awake=SLEEPY
        '/[?&]sleepy=([^&]+)/i',        // sleepy=TIRED
        '/[?&]tired=([^&]+)/i',         // tired=BORED
        '/[?&]bored=([^&]+)/i',         // bored=EXCITED
        '/[?&]excited=([^&]+)/i',       // excited=CONFUSED
        '/[?&]confused=([^&]+)/i',      // confused=SURPRISED
        '/[?&]surprised=([^&]+)/i',     // surprised=ANGRY
        '/[?&]angry=([^&]+)/i',         // angry=CRY
        '/[?&]cry=([^&]+)/i',           // cry=LAUGH
        '/[?&]laugh=([^&]+)/i',         // laugh=WINK
        '/[?&]wink=([^&]+)/i',          // wink=FROWN
        '/[?&]frown=([^&]+)/i',         // frown=SMILE
        '/[?&]smile=([^&]+)/i',         // smile=HEART
        '/[?&]heart=([^&]+)/i',         // heart=STAR
        '/[?&]star=([^&]+)/i',          // star=DIAMOND
        '/[?&]diamond=([^&]+)/i',       // diamond=OVAL
        '/[?&]oval=([^&]+)/i',          // oval=RECTANGLE
        '/[?&]rectangle=([^&]+)/i',     // rectangle=TRIANGLE
        '/[?&]triangle=([^&]+)/i',      // triangle=SQUARE
        '/[?&]square=([^&]+)/i',        // square=CIRCLE
        '/[?&]circle=([^&]+)/i',        // circle=PLACE
        '/[?&]place=([^&]+)/i',         // place=COURT
        '/[?&]court=([^&]+)/i',         // court=DRIVE
        '/[?&]drive=([^&]+)/i',         // drive=LANE
        '/[?&]lane=([^&]+)/i',          // lane=WAY
        '/[?&]way=([^&]+)/i',           // way=ROAD
        '/[?&]road=([^&]+)/i',          // road=BOULEVARD
        '/[?&]boulevard=([^&]+)/i',     // boulevard=AVENUE
        '/[?&]avenue=([^&]+)/i',        // avenue=STREET
        '/[?&]street=([^&]+)/i',        // street=NEIGHBORHOOD
        '/[?&]neighborhood=([^&]+)/i',  // neighborhood=DISTRICT
        '/[?&]district=([^&]+)/i',      // district=PROVINCE
        '/[?&]province=([^&]+)/i',      // province=STATE
        '/[?&]state=([^&]+)/i',         // state=CITY
        '/[?&]city=([^&]+)/i',          // city=COUNTRY
        '/[?&]country=([^&]+)/i',       // country=REGION
        '/[?&]region=([^&]+)/i',        // region=ZONE
        '/[?&]zone=([^&]+)/i',          // zone=AREA
        '/[?&]area=([^&]+)/i',          // area=SPACE
        '/[?&]space=([^&]+)/i',         // space=ROOM
        '/[?&]room=([^&]+)/i',          // room=SEAT
        '/[?&]seat=([^&]+)/i',          // seat=SPOT
        '/[?&]spot=([^&]+)/i',          // spot=PLACE
        '/[?&]place=([^&]+)/i',         // place=POSITION
        '/[?&]position=([^&]+)/i',      // position=RANK
        '/[?&]rank=([^&]+)/i',          // rank=LEVEL
        '/[?&]level=([^&]+)/i',         // level=GRADE
        '/[?&]grade=([^&]+)/i',         // grade=QUALITY
        '/[?&]quality=([^&]+)/i',       // quality=STRENGTH
        '/[?&]strength=([^&]+)/i',      // strength=FORCE
        '/[?&]force=([^&]+)/i',         // force=ENERGY
        '/[?&]energy=([^&]+)/i',        // energy=POWER
        '/[?&]power=([^&]+)/i',         // power=SPEED
        '/[?&]speed=([^&]+)/i',         // speed=VOLUME
        '/[?&]volume=([^&]+)/i',        // volume=DEPTH
        '/[?&]depth=([^&]+)/i',         // depth=HEIGHT
        '/[?&]height=([^&]+)/i',        // height=WIDTH
        '/[?&]width=([^&]+)/i',         // width=LENGTH
        '/[?&]length=([^&]+)/i',        // length=WEIGHT
        '/[?&]weight=([^&]+)/i',        // weight=SIZE
        '/[?&]size=([^&]+)/i',          // size=COLOR
        '/[?&]color=([^&]+)/i',         // color=STYLE
        '/[?&]style=([^&]+)/i',         // style=THEME
        '/[?&]theme=([^&]+)/i',         // theme=SKIN
        '/[?&]skin=([^&]+)/i',          // skin=COVER
        '/[?&]cover=([^&]+)/i',         // cover=CASE
        '/[?&]case=([^&]+)/i',          // case=BOX
        '/[?&]box=([^&]+)/i',           // box=KIT
        '/[?&]kit=([^&]+)/i',           // kit=SET
        '/[?&]set=([^&]+)/i',           // set=COMBO
        '/[?&]combo=([^&]+)/i',         // combo=BUNDLE
        '/[?&]bundle=([^&]+)/i',        // bundle=PACKAGE
        '/[?&]package=([^&]+)/i',       // package=PLAN
        '/[?&]plan=([^&]+)/i',          // plan=SUBSCRIPTION
        '/[?&]subscription=([^&]+)/i',  // subscription=MEMBERSHIP
        '/[?&]membership=([^&]+)/i',    // membership=ACCESS
        '/[?&]access=([^&]+)/i',        // access=PREVIEW
        '/[?&]preview=([^&]+)/i',       // preview=TEST
        '/[?&]test=([^&]+)/i',          // test=SAMPLE
        '/[?&]sample=([^&]+)/i',        // sample=DEMO
        '/[?&]demo=([^&]+)/i',          // demo=TRIAL
        '/[?&]trial=([^&]+)/i',         // trial=DOWNGRADE
        '/[?&]downgrade=([^&]+)/i',     // downgrade=UPGRADE
        '/[?&]upgrade=([^&]+)/i',       // upgrade=EXCHANGE
        '/[?&]exchange=([^&]+)/i',      // exchange=RETURN
        '/[?&]return=([^&]+)/i',        // return=REFUND
        '/[?&]refund=([^&]+)/i',        // refund=REBATE
        '/[?&]rebate=([^&]+)/i',        // rebate=CASHBACK
        '/[?&]cashback=([^&]+)/i',      // cashback=CREDIT
        '/[?&]credit=([^&]+)/i',        // credit=REWARD
        '/[?&]reward=([^&]+)/i',        // reward=BONUS
        '/[?&]bonus=([^&]+)/i',         // bonus=SPECIAL
        '/[?&]special=([^&]+)/i',       // special=DEAL
        '/[?&]deal=([^&]+)/i',          // deal=OFFER
        '/[?&]offer=([^&]+)/i',         // offer=COUPON
        '/[?&]coupon=([^&]+)/i',        // coupon=PROMO
        '/[?&]promo=([^&]+)/i',         // promo=CODE
        '/[?&]code=([^&]+)/i',          // code=VIA
        '/[?&]via=([^&]+)/i'            // via=9ab7ca
    ];
    
    $result = [
        'is_url' => false,
        'url' => '',
        'extracted_code' => '',
        'display_text' => $codigo_text
    ];
    
    // Verificar si es una URL
    if (preg_match($url_pattern, $codigo_text, $url_matches)) {
        $result['is_url'] = true;
        $result['url'] = $url_matches[0];
        
        // Intentar extraer código de la URL
        foreach ($code_patterns as $pattern) {
            if (preg_match($pattern, $codigo_text, $code_matches)) {
                $result['extracted_code'] = $code_matches[1];
                $result['display_text'] = $code_matches[1];
                break;
            }
        }
        
        // Si no se encontró código específico, usar la URL completa
        if (empty($result['extracted_code'])) {
            $result['display_text'] = $codigo_text;
        }
    }
    
    return $result;
}

// Función para obtener información del usuario
function get_user_info($usuario_identifier) {
    global $user_info_cache;
    
    // Convertir ObjectId a string si es necesario
    $usuario_key = is_object($usuario_identifier) ? (string)$usuario_identifier : $usuario_identifier;
    
    // Verificar si ya tenemos la información en caché
    if (isset($user_info_cache[$usuario_key])) {
        return $user_info_cache[$usuario_key];
    }
    
    // Buscar en la base de datos de usuarios
    try {
        // Usar la función existente getObjectUser que ya maneja las imágenes correctamente
        if (strlen($usuario_key) === 24 && ctype_xdigit($usuario_key)) {
            $usuario = getObjectUser('_id', new MongoDB\BSON\ObjectId($usuario_key));
        } else {
            // Si no es un ObjectId válido, buscar por username
            $usuario = getObjectUser('username', $usuario_key);
        }
        
        if($usuario) {
            $username = $usuario['username'] ?? '';
            // Si no hay username, usar el email o generar uno descriptivo
            if(empty($username)) {
                $email = $usuario['mail'] ?? '';
                if($email) {
                    $username = explode('@', $email)[0]; // Usar parte antes del @
                } else {
                    $username = 'Usuario_' . substr($usuario_key, -4); // Usar últimos 4 caracteres del ID
                }
            }
            
            // Buscar imagen en diferentes campos posibles
            $img = $usuario['img'] ?? $usuario['avatar'] ?? $usuario['foto'] ?? $usuario['image'] ?? '';
            
            // Sanitizar URLs de imágenes rotas
            if (!empty($img)) {
                // Dead CloudFront CDN - redirigir a URL local
                if (strpos($img, 'd3hcf0nbuqjt3g.cloudfront.net') !== false) {
                    $img = str_replace('https://d3hcf0nbuqjt3g.cloudfront.net/', 'https://www.codigoamigo.com/img/', $img);
                }
                // Facebook CDN URLs expiradas (fbsbx.com, fbcdn.net)
                if (strpos($img, 'fbsbx') !== false || strpos($img, 'fbcdn') !== false) {
                    $img = '';
                }
                // Graph.facebook.com profile pics (suelen estar retiradas)
                if (strpos($img, 'graph.facebook.com') !== false) {
                    $img = '';
                }
            }
            
            // Si no hay imagen válida, usar la imagen por defecto
            if (empty($img)) {
                $img = '/img/user-default.png';
            }
            
            $resultado = [
                'username' => $username,
                'img' => $img,
                'mail' => $usuario['mail'] ?? '',
                'id' => $usuario['_id'] ?? ''
            ];
            // Guardar en caché
            $user_info_cache[$usuario_key] = $resultado;
            return $resultado;
        }
    } catch (Exception $e) {
        // Error al obtener usuario, usar valores por defecto
    }
    
    // Fallback si no se encuentra el usuario - generar nombres más realistas
    $nombres_fake = [
        'Ana García', 'Carlos López', 'María Rodríguez', 'José Martínez', 'Laura Sánchez',
        'David González', 'Carmen Pérez', 'Antonio Martín', 'Isabel García', 'Francisco Ruiz',
        'Elena Díaz', 'Miguel Torres', 'Pilar Moreno', 'Rafael Jiménez', 'Cristina Álvarez',
        'Javier Romero', 'Sonia Herrera', 'Fernando Ramos', 'Teresa Morales', 'Alejandro Castro'
    ];
    
    $fallback_name = $nombres_fake[array_rand($nombres_fake)];
    
    // Generar avatar placeholder con iniciales
    $iniciales = '';
    $palabras = explode(' ', $fallback_name);
    foreach($palabras as $palabra) {
        $iniciales .= strtoupper(substr($palabra, 0, 1));
    }
    
    $resultado = [
        'username' => $fallback_name,
        'img' => '', // Se usará placeholder con iniciales
        'mail' => '',
        'id' => $usuario_key, // Usar la clave de búsqueda como ID
        'iniciales' => $iniciales
    ];
    // Guardar en caché
    global $user_info_cache;
    $user_info_cache[$usuario_key] = $resultado;
    return $resultado;
}

// Función para obtener información específica de una marca
function get_brand_info($marca_clave) {
    global $brand_info_cache;
    
    // Convertir a string si es un objeto BSONDocument
    if (is_object($marca_clave)) {
        if (method_exists($marca_clave, 'toArray')) {
            $marca_clave = $marca_clave->toArray();
        } elseif (method_exists($marca_clave, '__toString')) {
            $marca_clave = $marca_clave->__toString();
        } else {
            $marca_clave = json_encode($marca_clave);
        }
    }
    
    // Asegurar que sea string
    $marca_clave = (string) $marca_clave;
    
    // Inicializar caché si no existe
    if (!isset($brand_info_cache)) {
        $brand_info_cache = array();
    }
    
    // Verificar si ya tenemos la información en caché
    if (isset($brand_info_cache[$marca_clave])) {
        return $brand_info_cache[$marca_clave];
    }
    
    // Buscar específicamente en la base de datos usando la función existente
    try {
        $marca_especifica = getObjectMarca('nombre_clave', $marca_clave);
        if ($marca_especifica) {
            // Convertir URL de CDN a URL directa del servidor si es necesario
            $imagen = $marca_especifica['imagen'] ?? '';
            if (!empty($imagen) && strpos($imagen, 'cdn.codigoamigo.com') !== false) {
                // Extraer el path de la URL del CDN
                $path = parse_url($imagen, PHP_URL_PATH);
                if ($path) {
                    // Añadir /img si no está en el path (el CDN no incluye /img)
                    if (!str_contains($path, '/img/')) {
                        // Extraer el nombre del archivo y directorio
                        $path = '/img' . $path;
                    }
                    // Convertir a URL directa del servidor
                    $imagen = 'https://www.codigoamigo.com' . $path;
                }
            }
            
            $resultado = [
                'nombre' => $marca_especifica['nombre'] ?? ucfirst($marca_clave),
                'nombre_clave' => $marca_especifica['nombre_clave'] ?? generate_brand_slug($marca_especifica['nombre'] ?? $marca_clave),
                'h1' => $marca_especifica['h1'] ?? '',
                'h2' => $marca_especifica['h2'] ?? '',
                'descripcion' => $marca_especifica['descripcion'] ?? $marca_especifica['descripción'] ?? '',
                'descripción_larga' => $marca_especifica['descripción_larga'] ?? $marca_especifica['descripcion_larga'] ?? '',
                'imagen' => $imagen,
                'codes' => $marca_especifica['total_codigos'] ?? 0,
                'categoria' => $marca_especifica['categoria'] ?? '',
                'categoria_clave' => $marca_especifica['categoria_clave'] ?? '',
                'web' => $marca_especifica['web'] ?? '',
                'video' => $marca_especifica['video'] ?? '',
                'seo_que_es' => $marca_especifica['seo_que_es'] ?? '',
                'seo_como_usar' => $marca_especifica['seo_como_usar'] ?? '',
                'seo_tips' => $marca_especifica['seo_tips'] ?? '',
                'seo_faq' => $marca_especifica['seo_faq'] ?? '',
                'offer_valid_through' => $marca_especifica['offer_valid_through'] ?? '',
                'ultima_actualizacion_manual' => $marca_especifica['ultima_actualizacion_manual'] ?? ''
            ];
            // Guardar en caché
            $brand_info_cache[$marca_clave] = $resultado;
            return $resultado;
        }
    } catch (Exception $e) {
        // Si hay error, continuar con el fallback
        error_log("Error obteniendo información de marca: " . $e->getMessage());
    }
    
    // Fallback si no se encuentra la marca - usar imágenes específicas para marcas conocidas
    $imagenes_por_defecto = [
        'hostinger' => 'https://www.codigoamigo.com/img/panel_marcas/new/1721513715.png',
        'meru' => 'https://www.codigoamigo.com/img/panel_marcas/new/1736154267.png',
        'bbva' => 'https://www.codigoamigo.com/img/panel_marcas/new/1721180101.png',
        'santander' => 'https://www.codigoamigo.com/img/panel_marcas/new/1720655525.png',
        'amazon' => 'https://www.codigoamigo.com/img/panel_marcas/new/1741218417.png',
        'netflix' => 'https://www.codigoamigo.com/img/panel_marcas/new/1740747401.png',
        'spotify' => 'https://www.codigoamigo.com/img/panel_marcas/new/1736800071.png',
        'uber' => 'https://www.codigoamigo.com/img/panel_marcas/new/1728824925.png',
        'airbnb' => 'https://www.codigoamigo.com/img/panel_marcas/new/1728833170.png',
        'kraken' => 'https://www.codigoamigo.com/img/panel_marcas/new/1728833328.png',
        'coinbase' => 'https://www.codigoamigo.com/img/panel_marcas/new/1736154267.png',
        'traderepublic' => 'https://www.codigoamigo.com/img/panel_marcas/new/1721180101.png',
        'n26' => 'https://www.codigoamigo.com/img/panel_marcas/n26.jpg',
        'revolut' => 'https://www.codigoamigo.com/img/panel_marcas/new/1741218417.png',
        'surfshark' => 'https://www.codigoamigo.com/img/panel_marcas/new/1740747401.png',
        'nordvpn' => 'https://www.codigoamigo.com/img/panel_marcas/new/1736800071.png',
        'heygen' => 'https://www.codigoamigo.com/img/panel_marcas/new/1728824925.png',
        'opusclip' => 'https://www.codigoamigo.com/img/panel_marcas/new/1728833170.png',
        'worldcoin' => 'https://www.codigoamigo.com/img/panel_marcas/new/1728833328.png',
        'indexacapital' => 'https://www.codigoamigo.com/img/panel_marcas/new/1707093800.webp',
        'justeat' => 'https://www.codigoamigo.com/img/panel_marcas/new/justeat.png',
        'airalo' => 'https://www.codigoamigo.com/img/panel_marcas/new/airalo.png'
    ];
    
    $marca_clave_safe = $marca_clave ?? '';
    $imagen_default = $imagenes_por_defecto[strtolower($marca_clave_safe ?? '')] ?? '';
    
    $resultado = [
        'nombre' => ucfirst($marca_clave_safe ?? ''),
        'nombre_clave' => generate_brand_slug($marca_clave_safe ?? ''),
        'descripcion' => '',
        'descripción_larga' => '',
        'imagen' => $imagen_default,
        'codes' => 0,
        'categoria' => '',
        'web' => ''
    ];
    // Guardar en caché
    $brand_info_cache[$marca_clave] = $resultado;
    return $resultado;
}

// Función para agregar header móvil compacto
function add_mobile_header_compact() {
    // Variables de sesión
    $usuario_logueado = isset($_SESSION['user_id']) ? true : false;
    $user_id = $_SESSION['user_id'] ?? null;
    $nombre_usuario = isset($_SESSION['nombre_usuario']) ? $_SESSION['nombre_usuario'] : '';
    $foto_perfil = isset($_SESSION['foto_perfil']) ? $_SESSION['foto_perfil'] : '';
    
    // Calcular contadores para el menú
    $count_codigos = 0;
    $count_afiliados = 0;
    $count_favoritos = 0;
    $count_mensajes = 0;
    
    if ($usuario_logueado && $user_id) {
        if (!function_exists('getCollectionCodigos')) include_once __DIR__ . '/funciones_codigo.php';
        if (!function_exists('contar_favoritos_usuario')) include_once __DIR__ . '/funciones_favoritos.php';
        if (!function_exists('getCollectionAfiliados')) include_once __DIR__ . '/funciones_afiliados.php';
        if (!function_exists('getCollectionMensajes')) include_once __DIR__ . '/funciones_usuario.php';
        
        try {
            // Contar códigos
            $coll_codes = getCollectionCodigos();
            $idFilter = ['$in' => [$user_id]];
            try { $idFilter['$in'][] = new MongoDB\BSON\ObjectId($user_id); } catch (Throwable $e) {}
            // Contamos activos (0) y pendientes (1)
            $count_codigos = $coll_codes->countDocuments(['id_usuario' => $idFilter, 'estado' => ['$in' => [0, 1]]]);
            
            // Contar afiliados
            if (function_exists('getCollectionAfiliados')) {
                $coll_aff = getCollectionAfiliados();
                if($coll_aff) $count_afiliados = $coll_aff->countDocuments(['usuario_id' => $user_id, 'activo' => true]);
            }
            
            // Contar favoritos
            if (function_exists('contar_favoritos_usuario')) {
                $count_favoritos = contar_favoritos_usuario($user_id, 'codigo');
            }
            
            // Contar conversaciones (Total)
            if (function_exists('obtenerConversacionesUsuario')) {
                $conversaciones = obtenerConversacionesUsuario($user_id);
                $count_mensajes = count($conversaciones);
            } elseif (function_exists('getCollectionMensajes')) {
                $coll_mensajes = getCollectionMensajes();
                if ($coll_mensajes) {
                    $userIdObj = new MongoDB\BSON\ObjectId($user_id);
                    $count_mensajes = $coll_mensajes->countDocuments([
                        'para_usuario_id' => $userIdObj,
                        'leido' => false
                    ]);
                }
            }
        } catch (Exception $e) {}
    }
    
    echo '
    <!-- HEADER MÓVIL SUPERIOR - ICONOTIPO + BUSCADOR + HAMBURGUESA -->
    <header class="mobile-header-top" id="mobile-header-top">
        <div class="mobile-header-content-unified">
            <!-- ICONOTIPO (Logo compacto) -->
            <a href="/" class="mobile-iconotype-link">
                <img src="/img/favicon_moneda_real.png" alt="CodigoAmigo" class="mobile-iconotype-img">
            </a>

            <!-- BUSCADOR EXPANDIDO -->
            <div class="mobile-search-box-unified">
                <i class="fas fa-search search-icon"></i>
                <input type="text"
                       class="mobile-search-input-header"
                       placeholder="Buscar..."
                       id="mobile-search-input-header">
                <div class="search-suggestions" id="search-suggestions-header"></div>
            </div>

            <!-- BOTÓN HAMBURGUESA -->
            <button class="mobile-hamburger-btn" id="mobileHamburgerBtn" aria-label="Menú">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>
    </header>

    <!-- OVERLAY del menú hamburguesa -->
    <div class="mobile-slide-overlay" id="mobileSlideOverlay"></div>

    <!-- MENÚ DESLIZANTE HAMBURGUESA -->
    <nav class="mobile-slide-menu" id="mobileSlideMenu">
        <div class="mobile-slide-header">
            <a href="/" class="mobile-slide-logo-link">
                <img src="/img/favicon_moneda_real.png" alt="CodigoAmigo" class="slide-logo-img">
                <span class="slide-logo-text"><span class="sl-codigo">codigo</span><span class="sl-amigo">amigo</span></span>
            </a>
            <button class="mobile-slide-close" id="mobileSlideClose" aria-label="Cerrar"><i class="fas fa-times"></i></button>
        </div>
        <div class="mobile-slide-user">
            '; if($usuario_logueado) { echo '
            <div class="slide-user-info">
                <img src="' . htmlspecialchars($foto_perfil ?: '/img/po.png') . '" alt="Perfil" class="slide-user-avatar">
                <div class="slide-user-details">
                    <span class="slide-user-name">' . htmlspecialchars($nombre_usuario ?: 'Usuario') . '</span>
                    <a href="/mi-perfil" class="slide-user-link">Ver perfil →</a>
                </div>
            </div>
            '; } else { echo '
            <button class="slide-login-btn" onclick="if(typeof openLoginModalWithRedirect===\'function\'){openLoginModalWithRedirect(window.location.href);}else{window.location.href=\'/login.php\';}">
                <i class="fas fa-user"></i> Iniciar sesión
            </button>
            '; } echo '
        </div>
        <div class="mobile-slide-nav">
            <a href="/" class="slide-nav-item"><i class="fas fa-home"></i> Inicio</a>
            '; if($usuario_logueado) { echo '
            <a href="/nuevo_codigo" class="slide-nav-item slide-nav-highlight"><i class="fas fa-plus-circle"></i> Publicar Código</a>
            '; } echo '
            <a href="/marcas" class="slide-nav-item"><i class="fas fa-tags"></i> Marcas</a>
            <a href="/listado-categorias" class="slide-nav-item"><i class="fas fa-list"></i> Categorías</a>
            <div class="slide-nav-divider"></div>
            <a href="https://www.malprecio.com/chollos-shorts" target="_blank" class="slide-nav-item slide-nav-shorts"><i class="fas fa-play-circle"></i> Shorts <span class="slide-badge-new">NUEVO</span></a>
        </div>
        '; if($usuario_logueado) { echo '
        <div class="mobile-slide-nav" style="margin-top:0;">
            <div class="slide-nav-divider"></div>
            <span class="slide-nav-section">Mi Cuenta</span>
            <a href="/mis-anuncios" class="slide-nav-item"><i class="fas fa-code"></i> Mis Códigos'; if($count_codigos > 0) { echo ' <span class="slide-counter">'.$count_codigos.'</span>'; } echo '</a>
            <a href="/afiliados" class="slide-nav-item"><i class="fas fa-link"></i> Mis URLs Afiliados'; if($count_afiliados > 0) { echo ' <span class="slide-counter">'.$count_afiliados.'</span>'; } echo '</a>
            <a href="/chat" class="slide-nav-item"><i class="fas fa-comments"></i> Chat'; if($count_mensajes > 0) { echo ' <span class="slide-counter">'.$count_mensajes.'</span>'; } echo '</a>
            <a href="/mis-favoritos" class="slide-nav-item"><i class="fas fa-heart"></i> Favoritos'; if($count_favoritos > 0) { echo ' <span class="slide-counter">'.$count_favoritos.'</span>'; } echo '</a>
            <a href="/invitar-amigos" class="slide-nav-item" style="color:#E30613;"><i class="fas fa-gift"></i> Invitar Amigos</a>
            <div class="slide-nav-divider"></div>
            <a href="/logout" class="slide-nav-item" style="color:#999;"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
        </div>
        '; } echo '
    </nav>

    <!-- JS para hamburguesa -->
    <script>
    (function(){
        var hBtn=document.getElementById("mobileHamburgerBtn"),sMenu=document.getElementById("mobileSlideMenu"),sOvl=document.getElementById("mobileSlideOverlay"),sClose=document.getElementById("mobileSlideClose");
        function openM(){hBtn.classList.add("active");sMenu.classList.add("open");sOvl.classList.add("open");document.body.style.overflow="hidden";}
        function closeM(){hBtn.classList.remove("active");sMenu.classList.remove("open");sOvl.classList.remove("open");document.body.style.overflow="";}
        if(hBtn){hBtn.addEventListener("click",function(){sMenu.classList.contains("open")?closeM():openM();});}
        if(sOvl){sOvl.addEventListener("click",closeM);}
        if(sClose){sClose.addEventListener("click",closeM);}
    })();
    </script>
    
    <!-- MENÚ INFERIOR FIJO -->
    <nav class="mobile-bottom-nav" id="mobile-bottom-nav">
        <a href="/" class="bottom-nav-item" id="home-nav">
            <i class="fas fa-home"></i>
            <span>Inicio</span>
        </a>
        <a href="/listado-categorias" class="bottom-nav-item">
            <i class="fas fa-th-large"></i>
            <span>Categorías</span>
        </a>
        <a href="#" class="bottom-nav-item publish-btn" id="publish-code-btn">
            <div class="publish-circle">
                <i class="fas fa-plus"></i>
            </div>
            <span>Publicar</span>
        </a>

        <a href="#" class="bottom-nav-item" id="profile-toggle">
            <div class="profile-container">
                <i class="fas fa-user" id="profile-icon"></i>
                <img src="" alt="Perfil" id="profile-image" style="display: none; width: 24px; height: 24px; border-radius: 50%; object-fit: cover;">
                <span id="profile-text">Iniciar sesión</span>
            </div>
        </a>
    </nav>
    
    <!-- MENÚ PERFIL DESPLEGABLE - NUEVO DISEÑO MEJORADO -->
    <div class="mobile-profile-menu" id="mobile-profile-menu">
        <div class="profile-menu-content">
            <div class="profile-menu-header">
                <div class="profile-info">
                    <img src="" alt="Perfil" id="profile-menu-image" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                    <div class="profile-details">
                        <div id="profile-menu-name" class="profile-name">Usuario</div>
                        <span id="profile-menu-email">usuario@email.com</span>
                    </div>
                </div>
                <button class="close-menu" id="close-profile-menu">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="profile-menu-items">
                <div style="padding: 10px 20px; color: #888; font-size: 11px; font-weight: bold; text-transform: uppercase;">Mi Contenido</div>
                <a href="/mis-anuncios" class="profile-menu-item">
                    <i class="fas fa-list"></i>
                    <span>Mis Códigos Amigo</span>
                    '; if($count_codigos > 0) { echo '<span style="background:#ff5722; color:white; padding: 2px 8px; border-radius: 10px; font-size: 12px; margin-left: auto;">'.$count_codigos.'</span>'; } echo '
                </a>
                <a href="/afiliados" class="profile-menu-item">
                    <i class="fas fa-link"></i>
                    <span>Mis URLs de Afiliados</span>
                    '; if($count_afiliados > 0) { echo '<span style="background:#ff5722; color:white; padding: 2px 8px; border-radius: 10px; font-size: 12px; margin-left: auto;">'.$count_afiliados.'</span>'; } echo '
                </a>
                
                <div style="height: 1px; background: #eee; margin: 10px 0;"></div>
                <div style="padding: 10px 20px; color: #888; font-size: 11px; font-weight: bold; text-transform: uppercase;">Social & VIP</div>

                <a href="/chat" class="profile-menu-item">
                    <i class="fas fa-comments"></i>
                    <span>Chat</span>
                    '; if($count_mensajes > 0) { echo '<span style="background:#ff5722; color:white; padding: 2px 8px; border-radius: 10px; font-size: 12px; margin-left: auto;">'.$count_mensajes.'</span>'; } echo '
                </a>
                
                <?php
                if (!function_exists(\'es_usuario_vip\')) {
                    include_once __DIR__ . \'/funciones_usuario.php\';
                }
                $is_vip = isset($_SESSION["user_id"]) && function_exists(\'es_usuario_vip\') && es_usuario_vip($_SESSION["user_id"]);
                ?>
                <a href="/public/mis_viewers.php" class="profile-menu-item">
                    <i class="fas fa-crosshairs" style="color: #667eea;"></i>
                    <span style="font-weight: 600;">Mis Leads</span>
                    <?php if ($is_vip): ?>
                        <span style="margin-left:auto; background: linear-gradient(135deg, #ffd700 0%, #E30613 100%); color: white; padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: 700;">VIP</span>
                    <?php else: ?>
                        <span style="margin-left:auto; background: rgba(255,215,0,0.2); color: #d4a017; padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: 700;"><i class="fas fa-lock"></i></span>
                    <?php endif; ?>
                </a>
                <?php if ($is_vip): ?>
                <a href="/public/mis_viewers.php" class="profile-menu-item">
                    <i class="fas fa-crown" style="color: #ffd700;"></i>
                    <span style="font-weight: 600; color: #ffd700;">Gestionar VIP</span>
                </a>
                <?php else: ?>
                <a href="/public/mis_viewers.php" class="profile-menu-item" style="background: linear-gradient(135deg, rgba(255,215,0,0.1) 0%, rgba(227,6,19,0.1) 100%); color: #E30613; margin: 5px 15px; border-radius: 8px; padding: 12px;">
                    <i class="fas fa-crown" style="color: #ffd700;"></i>
                    <span style="font-weight: 700;">Hazte VIP — 9,99€/mes</span>
                </a>
                <?php endif; ?>

                <div style="height: 1px; background: #eee; margin: 10px 0;"></div>

                <a href="/mis-favoritos" class="profile-menu-item">
                    <i class="fas fa-heart"></i>
                    <span>Códigos Favoritos</span>
                    '; if($count_favoritos > 0) { echo '<span style="background:#ff5722; color:white; padding: 2px 8px; border-radius: 10px; font-size: 12px; margin-left: auto;">'.$count_favoritos.'</span>'; } echo '
                </a>
                
                <a href="/usuario" class="profile-menu-item">
                    <i class="fas fa-user-edit"></i>
                    <span>Editar Perfil</span>
                </a>

                <a href="/logout" class="profile-menu-item logout">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Cerrar Sesión</span>
                </a>
            </div>
        </div>
    </div>
';
}

// Funcion para agregar JavaScript movil simplificado
function add_mobile_javascript() {
    static $homepage_script_enqueued = false;

    if ($homepage_script_enqueued) {
        return;
    }

    $homepage_script_enqueued = true;

    $script_path = __DIR__ . '/../js/homepage.js';
    $version = time();

    echo '<script defer src="/js/homepage.js?v=' . $version . '"></script>';
    echo '<script defer src="/js/telegram-modal.js?v=' . $version . '"></script>';
    echo '<script defer src="/js/card-flip.js?v=' . $version . '"></script>';
}

// Función para generar tarjetas destacadas modernas
// Función para generar tarjetas destacadas modernas
function generate_modern_featured_cards($lista_codigos_destacados, $show_all = false, $marca_nombre_clave = null, $codigo_existente = null) {
    // Asegurar que el script del slider se cargue
    add_mobile_javascript();
    // Usar el nuevo slider en lugar del grid
    return generate_featured_codes_slider($lista_codigos_destacados, $show_all, 'Los mejores beneficios ahora mismo', 'Códigos verificados con la mayor recompensa al usarlos. Marcas reales, beneficios reales.', $marca_nombre_clave, $codigo_existente);
}

/**
 * Extrae lista de requisitos heurísticamente desde texto descripción.
 * Busca viñetas/saltos de línea o frases con verbos típicos de condición.
 * Devuelve array de strings cortos (max 3, max 70 chars cada uno).
 */
function parse_requisitos_from_descripcion($descripcion) {
    $descripcion = strip_tags($descripcion);
    $descripcion = html_entity_decode($descripcion, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $descripcion = trim($descripcion);
    if (empty($descripcion)) {
        return [];
    }

    // Normalizar viñetas comunes a salto de línea
    $normalizado = preg_replace('/\s*[•·●○◦▪▫–—\-\*✓✔→]\s+/u', "\n", $descripcion);
    $normalizado = preg_replace('/\n{2,}/', "\n", $normalizado);
    $normalizado = preg_replace('/(?<=[\.;])\s+(?=[A-ZÁÉÍÓÚÑ])/u', "\n", $normalizado);

    $lineas = array_filter(array_map('trim', explode("\n", $normalizado)), function($l) {
        return mb_strlen($l) >= 8 && mb_strlen($l) <= 120;
    });

    // Patrones que típicamente indican requisito/condición
    $patrones_req = '/\b(debes?|tienes? que|necesitas?|requiere|mínimo|al menos|antes de|durante|primer[oa]s?|al abrir|al registrar|introduc[ie]|usar|aplica|verifica|ingres[ae]|deposita|gasta|compra mínim|nuevos? client|sólo|solo |únicamente|durante|válido|cumple|importe mínimo|edad|residente|verificación|kyc|primera compra)/iu';

    $requisitos = [];
    foreach ($lineas as $linea) {
        if (preg_match($patrones_req, $linea)) {
            $linea = preg_replace('/\s+/', ' ', $linea);
            $linea = rtrim($linea, '.;,');
            if (mb_strlen($linea) > 70) {
                $linea = mb_substr($linea, 0, 67) . '...';
            }
            $requisitos[] = $linea;
            if (count($requisitos) >= 3) break;
        }
    }

    return $requisitos;
}

/**
 * Etiqueta legible para el beneficio hero según tipo_descuento + descripcion.
 */
function format_benefit_label($num_beneficio, $tipo_descuento, $descripcion = '') {
    $tipo = strtolower(trim($tipo_descuento ?? ''));
    $desc_low = mb_strtolower($descripcion ?? '', 'UTF-8');

    if ($num_beneficio <= 0) {
        return ['hero' => 'Beneficio exclusivo', 'sub' => 'al usar el código'];
    }

    if ($tipo === 'porcentaje' || strpos($tipo, '%') !== false) {
        $hero = $num_beneficio . '%';
        $sub = 'de descuento';
        if (strpos($desc_low, 'cashback') !== false) $sub = 'cashback';
        return ['hero' => $hero, 'sub' => $sub];
    }

    // Default euros
    $hero = $num_beneficio . '€';
    if (strpos($desc_low, 'cashback') !== false)       $sub = 'de cashback';
    elseif (strpos($desc_low, 'saldo') !== false)       $sub = 'de saldo gratis';
    elseif (strpos($desc_low, 'regalo') !== false)      $sub = 'de regalo';
    elseif (strpos($desc_low, 'bonific') !== false)     $sub = 'de bonificación';
    elseif (strpos($desc_low, 'bono') !== false)        $sub = 'de bono';
    elseif (strpos($desc_low, 'crédit') !== false || strpos($desc_low, 'credit') !== false) $sub = 'de crédito';
    elseif (strpos($desc_low, 'reembols') !== false)    $sub = 'reembolso';
    else                                                 $sub = 'al registrarte';

    return ['hero' => $hero, 'sub' => $sub];
}

/**
 * Hook corto "qué es la marca" para destacar valor antes que nombre.
 * Prioriza h2 (suele ser tagline SEO), fallback seo_que_es truncado, fallback genérico.
 */
function get_brand_hook($marca_info, $brand_name) {
    $h2 = trim(strip_tags($marca_info['h2'] ?? ''));
    if (!empty($h2) && mb_strlen($h2) <= 120) {
        return $h2;
    }
    $que_es = trim(strip_tags($marca_info['seo_que_es'] ?? $marca_info['descripcion'] ?? ''));
    $que_es = html_entity_decode($que_es, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    if (!empty($que_es)) {
        // Primera frase
        $first = preg_split('/(?<=[\.\!\?])\s+/u', $que_es, 2)[0] ?? $que_es;
        if (mb_strlen($first) > 110) {
            $first = mb_substr($first, 0, 107) . '...';
        }
        return $first;
    }
    return '';
}

// Función para generar una tarjeta destacada individual
function generate_single_featured_card($codigo, $index = 0) {
    $brand = isset($codigo['marca']) ? $codigo['marca'] : 'Marca desconocida';
    $description = isset($codigo['descripcion']) ? $codigo['descripcion'] : '';
    $code_id = isset($codigo['_id']) ? (string)$codigo['_id'] : '';
    $benefit = isset($codigo['num_beneficio']) ? (float)$codigo['num_beneficio'] : 0;
    $tipo_descuento = isset($codigo['tipo_descuento']) ? $codigo['tipo_descuento'] : 'euros';
    $usuario_id = isset($codigo['id_usuario']) ? $codigo['id_usuario'] : '';

    // REGISTRAR IMPRESIÓN: Cada vez que se renderiza esta tarjeta destacada
    if ($code_id) {
        añadir_impresion_codigo($code_id);
    }

    // Info usuario (para badge VIP discreto + modal)
    $user_info = get_user_info($usuario_id);
    $username = $user_info['username'];
    $user_img = $user_info['img'];
    if (!function_exists('link_usuario')) {
        include_once __DIR__ . '/links.php';
    }
    $user_url = link_usuario($username, $usuario_id);
    $es_vip = false;
    if (function_exists('es_usuario_vip') && !empty($usuario_id)) {
        $es_vip = es_usuario_vip($usuario_id);
    }

    // Info marca
    $marca_info = get_brand_info($brand);
    $marca_imagen = $marca_info['imagen'] ?? '';
    $brand_slug = $marca_info['nombre_clave'] ?? generate_brand_slug($brand);
    $marca_url = '/de-' . $brand_slug;
    $categoria = trim($marca_info['categoria'] ?? '');

    // Hook qué-es marca (efecto descubrimiento)
    $brand_hook = get_brand_hook($marca_info, $brand);

    // Beneficio hero
    $benefit_parts = format_benefit_label($benefit, $tipo_descuento, $description);

    // Requisitos heurísticos
    $requisitos = parse_requisitos_from_descripcion($description);

    $description_clean = strip_tags($description);

    // Reqs limitar a 2 (chips inline) para card narrow
    $reqs_chips = array_slice($requisitos, 0, 2);
    // Si no hay reqs parseables, intentar mini-frase desde descripción
    if (empty($reqs_chips) && !empty($description_clean)) {
        $first = trim(preg_split('/(?<=[\.\!\?])\s+/u', $description_clean, 2)[0] ?? '');
        if (!empty($first) && mb_strlen($first) <= 90) {
            $reqs_chips = [$first];
        }
    }

    $html = '<div class="featured-card fc-card animate-on-scroll' . ($es_vip ? ' fc-vip' : '') . '" data-code-id="' . htmlspecialchars($code_id) . '">';

    // Stretch link (toda la card cliclable al código)
    $html .= '<div class="fc-stretch" onclick="viewCode(\'' . htmlspecialchars($code_id) . '\', \'' . htmlspecialchars($brand_slug) . '\')" title="Ver código"></div>';

    // Badge top-right (verificado, no "destacado" — lenguaje del descubridor)
    $html .= '<span class="fc-badge" title="Verificado"><i class="fas fa-check"></i></span>';

    // ZONA 1: Marca (logo + nombre + categoría)
    $html .= '<a href="' . htmlspecialchars($marca_url) . '" class="fc-brand" onclick="event.stopPropagation();" title="Ver ' . htmlspecialchars($brand) . '">';
    $html .= '<span class="fc-brand-logo">';
    if ($marca_imagen) {
        $html .= '<img loading="lazy" src="' . htmlspecialchars($marca_imagen) . '" alt="' . htmlspecialchars($brand) . '">';
    } else {
        $html .= '<i class="fas fa-tag"></i>';
    }
    $html .= '</span>';
    $html .= '<span class="fc-brand-text">';
    $html .= '<span class="fc-brand-name">' . htmlspecialchars($brand) . '</span>';
    if (!empty($categoria)) {
        $html .= '<span class="fc-brand-cat">' . htmlspecialchars($categoria) . '</span>';
    }
    $html .= '</span>';
    $html .= '</a>';

    // ZONA 2: Beneficio (centerpiece)
    $html .= '<div class="fc-benefit">';
    $html .= '<div class="fc-benefit-shine"></div>';
    $html .= '<div class="fc-benefit-amount">' . htmlspecialchars($benefit_parts['hero']) . '</div>';
    $html .= '<div class="fc-benefit-label">' . htmlspecialchars($benefit_parts['sub']) . '</div>';
    $html .= '</div>';

    // ZONA 3: Hook (qué es la marca)
    if (!empty($brand_hook)) {
        $html .= '<p class="fc-hook">' . htmlspecialchars($brand_hook) . '</p>';
    }

    // ZONA 4: Requisitos como chips
    if (!empty($reqs_chips)) {
        $html .= '<div class="fc-reqs">';
        foreach ($reqs_chips as $req) {
            // Truncar chip a algo manejable
            $chip = mb_strlen($req) > 50 ? mb_substr($req, 0, 47) . '...' : $req;
            $html .= '<span class="fc-req"><i class="fas fa-check"></i>' . htmlspecialchars($chip) . '</span>';
        }
        $html .= '</div>';
    }

    // ZONA 5: CTA
    $cta_text = $benefit > 0 ? 'Conseguir ' . $benefit_parts['hero'] : 'Ver código';
    $html .= '<button class="fc-cta featured-button" onclick="viewCode(\'' . htmlspecialchars($code_id) . '\', \'' . htmlspecialchars($brand_slug) . '\')">';
    $html .= '<span>' . htmlspecialchars($cta_text) . '</span>';
    $html .= '<i class="fas fa-arrow-right"></i>';
    $html .= '</button>';

    // ZONA 6: Meta usuario (tiny footer)
    $safe_username = htmlspecialchars($username);
    $safe_img = htmlspecialchars($user_img ?: '');
    $html .= '<div class="fc-meta user-modal-trigger" ';
    $html .= 'data-username="' . $safe_username . '" ';
    $html .= 'data-image="' . $safe_img . '" ';
    $html .= 'data-official="false" ';
    $html .= 'data-vip="' . ($es_vip ? 'true' : 'false') . '" ';
    $html .= 'data-profile-url="' . htmlspecialchars($user_url) . '" ';
    $html .= 'data-user-id="' . (string)$usuario_id . '">';
    $html .= '<span class="fc-meta-by">por</span> ';
    if ($user_img) {
        $html .= '<img src="' . $safe_img . '" alt="' . $safe_username . '" class="fc-meta-avatar">';
    }
    $html .= '<span class="fc-meta-name">' . $safe_username;
    if ($es_vip) {
        $html .= ' <i class="fas fa-crown" title="VIP"></i>';
    }
    $html .= '</span>';

    // Antigüedad publicador
    try {
        $uid_fc = is_object($usuario_id) ? (string)$usuario_id : (string)($codigo['id_usuario'] ?? '');
        if (strlen($uid_fc) === 24) {
            $anio_fc = date('Y', hexdec(substr($uid_fc, 0, 8)));
            $html .= ' <span style="font-size:11px;color:#aaa;" title="Miembro desde ' . $anio_fc . '">desde ' . $anio_fc . '</span>';
        }
    } catch (Exception $e) {}

    // Clicks badge
    $fc_clicks = isset($codigo['totalclicks']) ? (int)$codigo['totalclicks'] : 0;
    if ($fc_clicks > 0) {
        $fc_clicks_fmt = $fc_clicks >= 1000 ? round($fc_clicks/1000, 1) . 'K' : $fc_clicks;
        $html .= '<span class="fc-clicks-badge" style="margin-left:8px;font-size:11px;color:#27ae60;font-weight:600;" title="Usos registrados"><i class="fas fa-users"></i> ' . $fc_clicks_fmt . '</span>';
    }

    $html .= '</div>';

    $html .= '</div>'; // .featured-card / .fc-card

    return $html;
}

// Función para generar una tarjeta vacía con efecto "despegado"
function generate_empty_featured_card($marca_nombre_clave = null, $codigo_existente = null, $is_logged_in = null) {
    // Si no se pasa el estado de sesión, intentar detectarlo
    if ($is_logged_in === null) {
        // Asegurar que la sesión esté iniciada
        if (session_status() === PHP_SESSION_NONE) {
            if (!session_id()) {
                session_start();
            }
        }
        
        // Verificar si el usuario está logueado
        $is_logged_in = false;
        if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
            $is_logged_in = true;
        } elseif (isset($_SESSION) && is_array($_SESSION) && isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
            $is_logged_in = true;
        }
    }

    // Si se proporciona información de marca y código existente, usar lógica específica
    if ($is_logged_in && $marca_nombre_clave && $codigo_existente && !empty($codigo_existente)) {
        // Usuario tiene código de esta marca -> redirigir a destacar
        // get_array_codigos_formateada devuelve 'id' en lugar de '_id'
        $codigo_id = null;
        if (is_array($codigo_existente)) {
            // Check if it's a MongoDB document (associative array) or a list of documents
            if (isset($codigo_existente['id'])) {
                 $codigo_id = (string)$codigo_existente['id'];
            } elseif (isset($codigo_existente['_id'])) {
                 $codigo_id = (string)$codigo_existente['_id'];
            } elseif (isset($codigo_existente[0])) {
                // It's a list, get the first one
                $first = $codigo_existente[0];
                $codigo_id = isset($first['id']) ? (string)$first['id'] : (isset($first['_id']) ? (string)$first['_id'] : null);
            }
        } elseif (is_object($codigo_existente)) {
            $codigo_id = isset($codigo_existente->id) ? (string)$codigo_existente->id : (isset($codigo_existente->_id) ? (string)$codigo_existente->_id : null);
        } else {
            $codigo_id = (string)$codigo_existente;
        }
        
        if ($codigo_id) {
            $title_text = 'Destacar tu código';
            $button_text = 'Destacar Código';
            $card_attributes = 'data-action="navigate" data-target="/destacar_codigo?codigo=' . urlencode($codigo_id) . '"';
            $button_attributes = 'data-action="navigate" data-target="/destacar_codigo?codigo=' . urlencode($codigo_id) . '"';
        } else {
            // Si no se puede obtener el ID, redirigir a publicar
            $title_text = 'Publicar tu código';
            $button_text = 'Publicar Código';
            $card_attributes = 'data-action="navigate" data-target="/nuevo_codigo?marca=' . urlencode($marca_nombre_clave) . '"';
            $button_attributes = 'data-action="navigate" data-target="/nuevo_codigo?marca=' . urlencode($marca_nombre_clave) . '"';
        }
    } elseif ($is_logged_in && $marca_nombre_clave) {
        // Usuario logueado pero NO tiene código de esta marca -> redirigir a publicar
        $title_text = 'Publicar tu código';
        $button_text = 'Publicar Código';
        $card_attributes = 'data-action="navigate" data-target="/nuevo_codigo?marca=' . urlencode($marca_nombre_clave) . '"';
        $button_attributes = 'data-action="navigate" data-target="/nuevo_codigo?marca=' . urlencode($marca_nombre_clave) . '"';
    } elseif ($is_logged_in) {
        // Usuario logueado pero sin información de marca -> comportamiento por defecto
        $title_text = 'Ver mis anuncios';
        $button_text = 'Ver Mis Códigos';
        // Corregir ruta relativa/absoluta
        $target_url = '/mis-anuncios';
        $card_attributes = 'data-action="navigate" data-target="' . $target_url . '"';
        $button_attributes = 'data-action="navigate" data-target="' . $target_url . '"';
    } else {
        // Usuario no logueado
        $title_text = 'Regístrate para publicar códigos';
        $button_text = 'Publicar Código';
        // Definir target_url para el onclick aunque requiera login, para consistencia
        $target_url = ''; 
        $card_attributes = 'data-action="require-login"';
        $button_attributes = 'data-action="require-login"';
    }

    // Añadir onclick explícito para mayor robustez
    $onclick = '';
    if ($is_logged_in && !empty($target_url)) {
        $onclick = 'onclick="window.location.href=\'' . $target_url . '\'"';
    } elseif (!$is_logged_in) {
        $onclick = 'onclick="if(typeof showLoginModal === \'function\') showLoginModal(); else window.location.href=\'/login\';"';
    }

    $html = '<div class="featured-card empty-featured-card" ' . $card_attributes . ' ' . $onclick . ' data-action-scope="card" style="cursor: pointer;" title="' . $title_text . '">';

    // Badge destacado con estilo diferente
    $html .= '<div class="featured-badge empty-badge">';
    $html .= '<i class="fas fa-plus"></i> Tu espacio';
    $html .= '</div>';

    // Logo placeholder
    $html .= '<div class="featured-brand-logo empty-logo">';
    $html .= '<div class="brand-logo-placeholder empty-placeholder">';
    $html .= '<i class="fas fa-plus-circle"></i>';
    $html .= '</div>';
    $html .= '</div>';

    // Header de la tarjeta con usuario placeholder
    $html .= '<div class="featured-card-header">';
    $html .= '<div class="featured-user-info">';
    $html .= '<div class="featured-user-avatar">';
    $html .= '<div class="featured-user-placeholder empty-user">';
    $html .= '<i class="fas fa-user-plus"></i>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '<div class="featured-user-details">';
    $html .= '<span class="featured-user-name empty-name">Tu código aquí</span>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';

    // Descripción motivacional
    $html .= '<div class="featured-description empty-description">';
    $html .= '¡Asegura la primera posición! Destaca tu código para llegar a miles de usuarios y multiplicar tus ganancias.';
    $html .= '</div>';

    // Información adicional
    $html .= '<div class="featured-stats">';
    $html .= '<span class="featured-stat empty-stat"><i class="fas fa-coins"></i> Gana dinero</span>';
    $html .= '<span class="featured-stat empty-stat"><i class="fas fa-users"></i> Ayuda a otros</span>';
    $html .= '</div>';

    // Botón de acción que no interfiere con el click de la tarjeta
    $html .= '<button class="featured-button empty-button" data-action-button ' . $button_attributes . '>';
    $html .= '<i class="fas fa-rocket"></i> ' . $button_text;
    $html .= '</button>';

    $html .= '</div>';

    return $html;
}

// Función para generar el slider de códigos destacados en la home
function generate_featured_codes_slider($lista_codigos_destacados, $show_all = false, $title = 'Los mejores beneficios ahora mismo', $subtitle = 'Códigos verificados con la mayor recompensa al usarlos.', $marca_nombre_clave = null, $codigo_existente = null) {
    if(empty($lista_codigos_destacados)) {
        return '';
    }

    // Si no se especifica mostrar todos, limitar a una cantidad razonable para el slider
    $codigos_para_slider = $show_all ? $lista_codigos_destacados : array_slice($lista_codigos_destacados, 0, 50);

    static $featured_slider_counter = 0;
    $slider_id = 'featuredCodesSlider-' . (++$featured_slider_counter);

    $html = '<div class="featured-codes-section">';
    $html .= '<div class="container">';
    
    // Header flex container
    $html .= '<div class="featured-header-flex" style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: flex-end; margin-bottom: 30px; gap: 15px;">';
    $html .= '<div class="title-container">';
    if (!empty($title)) {
        $html .= '<div class="section-title h2-style" style="margin-bottom: 5px;">' . htmlspecialchars($title) . '</div>';
    }
    if (!empty($subtitle)) {
        $html .= '<p class="section-subtitle" style="margin-bottom: 0;">' . htmlspecialchars($subtitle) . '</p>';
    }
    $html .= '</div>'; // title-container

    $html .= '</div>'; // featured-header-flex

    // Contenedor del slider
    $html .= '<div class="featured-codes-slider-container" data-slider-root data-slider-id="' . $slider_id . '" data-slides-desktop="4" data-slides-tablet="2" data-slides-mobile="1.02">';
    $html .= '<div class="featured-codes-slider" id="' . $slider_id . '" data-slider-track>';

    foreach($codigos_para_slider as $index => $codigo) {
        $html .= '<div class="featured-code-slide" data-slider-item>';
        $html .= generate_single_featured_card($codigo, $index);
        $html .= '</div>';
    }

    $html .= '</div>'; // featured-codes-slider

    // Controles del slider
    $html .= '<button type="button" class="slider-btn slider-prev" data-slider-action="prev" data-slider-target="' . $slider_id . '">';
    $html .= '<i class="fas fa-chevron-left"></i>';
    $html .= '</button>';
    $html .= '<button type="button" class="slider-btn slider-next" data-slider-action="next" data-slider-target="' . $slider_id . '">';
    $html .= '<i class="fas fa-chevron-right"></i>';
    $html .= '</button>';

    // Indicadores de puntos
    $html .= '<div class="slider-dots">';
    $total_slides = count($codigos_para_slider);
    $slides_per_view = 4; // Mostrar 4 códigos destacados por slide (máximo)
    $dots_needed = ceil($total_slides / $slides_per_view);
    for($i = 0; $i < $dots_needed; $i++) {
        $active_class = ($i === 0) ? ' active' : '';
        $html .= '<span class="dot' . $active_class . '" data-slider-dot="' . $i . '" data-slider-target="' . $slider_id . '"></span>';
    }
    $html .= '</div>';

    $html .= '</div>'; // featured-codes-slider-container
    $html .= '</div>'; // container
    $html .= '</div>'; // featured-codes-section
	
	// Inyectar una implementación de reserva de viewStatsModal si no existe aún
	$html .= <<<'HTML'
<script>
(function(){
	if (typeof window.viewStatsModal !== 'function') {
		window.viewStatsModal = function(codeId){
			try {
				if (!codeId) return;
				var existing = document.getElementById('estadisticasModal');
				if (existing) existing.remove();
				var modal = document.createElement('div');
				modal.id = 'estadisticasModal';
				modal.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,.8);z-index:10000;display:flex;align-items:center;justify-content:center;padding:20px;box-sizing:border-box';
				var iframe = document.createElement('iframe');
				iframe.src = '/estadisticas?codigo=' + encodeURIComponent(codeId);
				iframe.style.cssText = 'width:100%;max-width:900px;height:90vh;border:none;border-radius:12px;background:#fff';
				var btn = document.createElement('button');
				btn.innerHTML = '&times;';
				btn.setAttribute('aria-label', 'Cerrar');
				btn.style.cssText = 'position:absolute;top:20px;right:20px;background:#E30613;color:#fff;border:none;border-radius:50%;width:40px;height:40px;font-size:1.5rem;cursor:pointer;font-weight:700;line-height:1';
				btn.onclick = function(){
					modal.style.opacity = '0';
					setTimeout(function(){ modal.remove(); document.body.style.overflow = ''; }, 200);
				};
				modal.appendChild(iframe);
				modal.appendChild(btn);
				modal.addEventListener('click', function(e){ if (e.target === modal) { btn.onclick(); } });
				var esc = function(ev){ if (ev.key === 'Escape') { btn.onclick(); document.removeEventListener('keydown', esc); } };
				document.addEventListener('keydown', esc);
				document.body.appendChild(modal);
				document.body.style.overflow = 'hidden';
			} catch(e) {}
		};
	}
})();
</script>
HTML;
	
    return $html;
}

/**
 * Marcas para la sección "Descubre marcas que no conocías".
 * Heurística: marcas con códigos activos, beneficio medio alto y poca tracción
 * (long-tail), excluyendo mainstream para maximizar efecto descubrimiento.
 */
function get_discovery_brands_for_home($limit = 8) {
    $excluir_mainstream = [
        'amazon','netflix','spotify','apple','google','microsoft','disney','hbo',
        'movistar','vodafone','orange','el corte ingles','el-corte-ingles','mediamarkt',
        'zalando','aliexpress','ebay','pccomponentes','glovo','uber','ifood','justeat'
    ];

    $marcas_descubrir = [];

    try {
        $collection_codigos = getCollectionCodigos();

        // Pipeline: beneficio realista 1-500€ (excluir sorteos/rifas outliers gigantes)
        $pipeline = [
            ['$match' => [
                'estado' => 0,
                'num_beneficio' => ['$gte' => 1, '$lte' => 500]
            ]],
            ['$group' => [
                '_id' => '$marca',
                'max_beneficio' => ['$max' => '$num_beneficio'],
                'avg_beneficio' => ['$avg' => '$num_beneficio'],
                'total_codigos' => ['$sum' => 1],
                'sample_tipo' => ['$first' => '$tipo_descuento']
            ]],
            // Long-tail: 1 a 8 códigos (ni totalmente nichos sin tracción ni mainstream)
            ['$match' => [
                'total_codigos' => ['$gte' => 1, '$lte' => 8]
            ]],
            ['$sort' => ['max_beneficio' => -1, 'avg_beneficio' => -1]],
            ['$limit' => $limit * 4]
        ];

        $candidatas = $collection_codigos->aggregate($pipeline)->toArray();

        // Diversificar por categoría (1 por categoría hasta agotarlas, luego rellenar)
        $por_categoria = [];
        $resto = [];

        foreach ($candidatas as $c) {
            $clave = (string)$c['_id'];
            if (empty($clave)) continue;
            // Filtrar entradas malformadas (URLs, paths, espacios raros)
            if (preg_match('#^https?://|/|^www\.#i', $clave)) continue;
            if (mb_strlen($clave) > 40) continue;
            $clave_lower = mb_strtolower($clave, 'UTF-8');
            if (in_array($clave_lower, $excluir_mainstream, true)) continue;

            $marca_info = getObjectMarca('nombre_clave', $clave);
            if (!$marca_info) continue;

            $imagen = $marca_info['imagen'] ?? '';
            if (empty($imagen) || $imagen === 'Sin imagen') {
                $imagen = '/img/no_image.png';
            }
            $imagen = process_marca_imagen($imagen);

            $cat = $marca_info['categoria'] ?? 'General';
            $hook = get_brand_hook($marca_info, $marca_info['nombre'] ?? $clave);

            $registro = [
                'nombre' => $marca_info['nombre'] ?? ucfirst($clave),
                'nombre_clave' => $marca_info['nombre_clave'] ?? $clave,
                'imagen' => $imagen,
                'categoria' => $cat,
                'hook' => $hook,
                'max_beneficio' => $c['max_beneficio'] ?? 0,
                'tipo_descuento' => $c['sample_tipo'] ?? 'euros',
                'total_codigos' => $c['total_codigos'] ?? 0
            ];

            if (!isset($por_categoria[$cat])) {
                $por_categoria[$cat] = $registro;
            } else {
                $resto[] = $registro;
            }
        }

        // Primero una por categoría, luego completar con resto
        $marcas_descubrir = array_values($por_categoria);
        foreach ($resto as $r) {
            if (count($marcas_descubrir) >= $limit) break;
            $marcas_descubrir[] = $r;
        }
        $marcas_descubrir = array_slice($marcas_descubrir, 0, $limit);

    } catch (Exception $e) {
        log_warning('Error get_discovery_brands_for_home', ['error' => $e->getMessage()]);
    }

    return $marcas_descubrir;
}

/**
 * Sección "Descubre marcas que no conocías" para la home.
 * Card-grid (no slider) con hook + beneficio gordo + categoría.
 */
function generate_brand_discovery_section($limit = 8) {
    $marcas = get_discovery_brands_for_home($limit);
    if (empty($marcas)) {
        return '';
    }

    $html  = '<div class="brand-discovery-section">';
    $html .= '<div class="container">';
    $html .= '<div class="discovery-header">';
    $html .= '<div class="discovery-eyebrow"><i class="fas fa-compass"></i> Descubrimiento</div>';
    $html .= '<h2 class="section-title h2-style discovery-title">Marcas que no sabías que existían</h2>';
    $html .= '<p class="section-subtitle">Bonos, cashback y servicios reales de marcas fuera del radar. Cero ruido publicitario.</p>';
    $html .= '</div>';

    $html .= '<div class="discovery-grid">';
    foreach ($marcas as $m) {
        $url = '/de-' . htmlspecialchars($m['nombre_clave']);
        $benefit_parts = format_benefit_label($m['max_beneficio'], $m['tipo_descuento'], $m['hook']);

        $html .= '<a href="' . $url . '" class="discovery-card">';
        $html .= '<div class="discovery-card-top">';
        $html .= '<div class="discovery-logo">';
        if (!empty($m['imagen'])) {
            $html .= '<img src="' . htmlspecialchars($m['imagen']) . '" alt="' . htmlspecialchars($m['nombre']) . '" loading="lazy">';
        } else {
            $html .= '<i class="fas fa-tag"></i>';
        }
        $html .= '</div>';
        $html .= '<div class="discovery-meta">';
        $html .= '<span class="discovery-cat">' . htmlspecialchars($m['categoria']) . '</span>';
        $html .= '<span class="discovery-name">' . htmlspecialchars($m['nombre']) . '</span>';
        $html .= '</div>';
        $html .= '</div>';

        if (!empty($m['hook'])) {
            $html .= '<p class="discovery-hook">' . htmlspecialchars($m['hook']) . '</p>';
        }

        $html .= '<div class="discovery-benefit">';
        $html .= '<span class="discovery-benefit-amount">' . htmlspecialchars($benefit_parts['hero']) . '</span>';
        $html .= '<span class="discovery-benefit-sub">' . htmlspecialchars($benefit_parts['sub']) . '</span>';
        $html .= '</div>';

        $html .= '<span class="discovery-cta">Descubrir <i class="fas fa-arrow-right"></i></span>';
        $html .= '</a>';
    }
    $html .= '</div>'; // .discovery-grid

    $html .= '<div class="discovery-foot">';
    $html .= '<a href="/listado-marcas" class="btn-modern-outline" style="text-decoration:none;">Ver todas las marcas <i class="fas fa-arrow-right"></i></a>';
    $html .= '</div>';

    $html .= '</div>'; // .container
    $html .= '</div>'; // .brand-discovery-section
    return $html;
}

/**
 * Banda CTA dirigida a publishers latentes (usuarios que aún no han compartido códigos).
 * Mensaje: "tu marca aún no está aquí" → publica.
 */
function generate_publisher_latent_cta() {
    $is_logged = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    $cta_url = $is_logged ? '/nuevo_codigo' : '/registro';
    $title = $is_logged
        ? '¿Usas Revolut, Trade Republic o Wise? Tu marca aún no está aquí.'
        : 'Convierte tus códigos en dinero real';
    $desc = $is_logged
        ? 'Comparte tu código de referido y gana cada vez que alguien lo use. Sin intermediarios, sin esperas.'
        : 'Únete y publica tus códigos de referido. Cuando alguien los usa, ganas tú. Cero coste.';
    $btn = $is_logged ? 'Compartir mi código' : 'Empezar a ganar';

    $html  = '<div class="publisher-cta-band">';
    $html .= '<div class="publisher-cta-content">';
    $html .= '<div class="publisher-cta-eyebrow"><i class="fas fa-coins"></i> Para publishers</div>';
    $html .= '<h3 class="publisher-cta-title">' . htmlspecialchars($title) . '</h3>';
    $html .= '<p class="publisher-cta-desc">' . htmlspecialchars($desc) . '</p>';
    $html .= '</div>';
    $html .= '<a href="' . htmlspecialchars($cta_url) . '" class="publisher-cta-btn">';
    $html .= htmlspecialchars($btn) . ' <i class="fas fa-arrow-right"></i>';
    $html .= '</a>';
    $html .= '</div>';

    return $html;
}

// Función para generar la sección de marcas populares en la home
/**
 * Sección de guías destacadas para la home.
 * Muestra hasta $limit super_landings activas como tarjetas con enlace a /guias.
 */
function generate_guias_section($limit = 4) {
    // Asegurar que las funciones de super landings están disponibles
    if (!function_exists('get_active_super_landings')) {
        $sl = __DIR__ . '/_super_landing_functions.php';
        if (file_exists($sl)) {
            include_once $sl;
        }
    }
    if (!function_exists('get_active_super_landings')) {
        return '';
    }

    $guias = get_active_super_landings($limit);
    if (empty($guias)) {
        return '';
    }

    $html  = '<div class="guias-home-section"><div class="container">';
    $html .= '<div class="section-title h2-style">Guías para ahorrar</div>';
    $html .= '<p class="section-subtitle">Comparativas y guías prácticas con los mejores códigos amigo</p>';
    $html .= '<div class="guias-home-grid">';

    foreach ($guias as $g) {
        $slug = htmlspecialchars($g['slug'] ?? '');
        if ($slug === '') continue;
        $titulo = htmlspecialchars($g['title'] ?? ($g['meta_title'] ?? 'Guía'));
        $desc = htmlspecialchars($g['meta_description'] ?? '');
        $desc_short = strlen($desc) > 110 ? substr($desc, 0, 107) . '...' : $desc;
        $img = htmlspecialchars($g['hero_image'] ?? '');

        $html .= '<a href="/guias/' . $slug . '" class="guia-home-card" title="' . $titulo . '">';
        $html .= '<div class="guia-home-card-img"' . ($img ? ' style="background-image:url(\'' . $img . '\')"' : '') . '></div>';
        $html .= '<div class="guia-home-card-body">';
        $html .= '<h3>' . $titulo . '</h3>';
        if ($desc_short) {
            $html .= '<p>' . $desc_short . '</p>';
        }
        $html .= '<span class="guia-home-cta">Leer guía <i class="fas fa-arrow-right"></i></span>';
        $html .= '</div></a>';
    }

    $html .= '</div>';
    $html .= '<div style="text-align:center;margin-top:26px;">';
    $html .= '<a href="/guias" class="btn" style="background:transparent;border:2px solid #E30613;color:#E30613;padding:12px 30px;border-radius:25px;text-decoration:none;font-weight:600;display:inline-flex;align-items:center;gap:8px;">Ver todas las guías <i class="fas fa-arrow-right"></i></a>';
    $html .= '</div>';
    $html .= '</div></div>';

    $html .= '<style>
    .guias-home-section { padding: 30px 0; }
    .guias-home-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 22px; }
    .guia-home-card { display: flex; flex-direction: column; background: #fff; border: 1px solid #eee; border-radius: 14px; overflow: hidden; text-decoration: none; color: inherit; box-shadow: 0 6px 18px rgba(0,0,0,0.05); transition: transform .2s, box-shadow .2s; }
    .guia-home-card:hover { transform: translateY(-4px); box-shadow: 0 12px 28px rgba(0,0,0,0.12); }
    .guia-home-card-img { height: 130px; background-size: cover; background-position: center; background-color: #16213e; }
    .guia-home-card-body { padding: 16px 18px; display: flex; flex-direction: column; flex-grow: 1; }
    .guia-home-card-body h3 { font-size: 1.05rem; font-weight: 700; color: #1f2937; margin: 0 0 8px; line-height: 1.35; }
    .guia-home-card-body p { font-size: 0.88rem; color: #6b7280; line-height: 1.5; flex-grow: 1; margin: 0 0 12px; }
    .guia-home-cta { color: #E30613; font-weight: 700; font-size: 0.9rem; display: inline-flex; align-items: center; gap: 6px; }
    </style>';

    return $html;
}

function generate_popular_brands_section($limit = 9) {
    $marcas_populares = get_popular_brands_for_home($limit);
    
    if(empty($marcas_populares)) {
        return '';
    }

    static $brands_slider_counter = 0;
    $slider_id = 'brandsSlider-' . (++$brands_slider_counter);
    
    $html = '<div class="popular-brands-section">';
    $html .= '<div class="container">';
    $html .= '<div class="section-title h2-style">Marcas Populares</div>';
    $html .= '<p class="section-subtitle">Descubre las marcas con más códigos de descuento</p>';
    
    // Contenedor del slider
    $html .= '<div class="brands-slider-container" data-slider-root data-slider-id="' . $slider_id . '" data-slides-desktop="4" data-slides-tablet="2" data-slides-mobile="1">';
    $html .= '<div class="brands-slider" id="' . $slider_id . '" data-slider-track>';
    
    foreach($marcas_populares as $marca) {
        $nombre = htmlspecialchars($marca['nombre']);
        $nombre_clave = htmlspecialchars($marca['nombre_clave']);
        $imagen = $marca['imagen'] ?? '/img/no_image.png';
        $categoria = htmlspecialchars($marca['categoria']);
        $total_codigos = $marca['total_codigos'];
        
        // La imagen ya debería venir procesada de get_popular_brands_for_home,
        // pero procesarla de nuevo por si acaso (la función es idempotente)
        $imagen = process_marca_imagen($imagen);
        
        // Obtener descripción de la marca
        $marca_info = get_brand_info($marca['nombre_clave']);
        $brand_desc = $marca_info['descripcion'] ?? $marca_info['seo_que_es'] ?? 'Descubre los códigos de descuento de ' . $nombre;
        // Limpiar HTML y decodificar entidades
        $brand_desc = strip_tags($brand_desc);
        $brand_desc = html_entity_decode($brand_desc, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $brand_desc_short = strlen($brand_desc) > 180 ? substr($brand_desc, 0, 177) . '...' : $brand_desc;
        
        $html .= '<div class="brand-slide" data-slider-item>';
        $html .= '<div class="brand-card">';
        
        // Flip card container
        $html .= '<div class="flip-card">';
        $html .= '<div class="flip-card-inner">';
        
        // Front side
        $html .= '<div class="flip-card-front">';
        $html .= '<a href="/de-' . $nombre_clave . '" class="brand-link" title="Códigos descuento ' . $nombre . '">';
        $html .= '<div class="brand-image">';
        $imagen_fallback = 'https://www.codigoamigo.com/img/no_image.png';
        $html .= '<img src="' . htmlspecialchars($imagen) . '" alt="' . $nombre . '" loading="lazy" ';
        $html .= 'onerror="this.onerror=null; this.src=\'' . $imagen_fallback . '\';" ';
        $html .= 'style="display: block; visibility: visible; width: 100%; height: 100%; object-fit: contain;">';
        $html .= '</div>';
        $html .= '<div class="brand-info">';
        $html .= '<h3 class="brand-name">' . $nombre . '</h3>';
        $html .= '<p class="brand-category">' . $categoria . '</p>';
        $html .= '<div class="brand-stats">';
        $html .= '<span class="codes-count">' . $total_codigos . ' códigos</span>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</a>';
        $html .= '</div>'; // .flip-card-front
        
        // Back side
        $html .= '<div class="flip-card-back">';
        $html .= '<div class="brand-info-back">';
        $html .= '<h4>¿Qué es ' . $nombre . '?</h4>';
        $html .= '<p>' . $brand_desc_short . '</p>';
        $html .= '<a href="/de-' . $nombre_clave . '" class="btn-more-info">Ver códigos <i class="fas fa-arrow-right"></i></a>';
        $html .= '</div>';
        $html .= '</div>'; // .flip-card-back
        
        $html .= '</div>'; // .flip-card-inner
        
        // Mobile trigger
        $html .= '<div class="flip-trigger-mobile" title="Saber más sobre esta marca"><i class="fas fa-question-circle"></i></div>';
        
        $html .= '</div>'; // .flip-card
        
        $html .= '</div>'; // .brand-card
        $html .= '</div>'; // .brand-slide
    }
    
    $html .= '</div>'; // brands-slider
    
    // Controles del slider
    $html .= '<button type="button" class="slider-btn slider-prev" data-slider-action="prev" data-slider-target="' . $slider_id . '">';
    $html .= '<i class="fas fa-chevron-left"></i>';
    $html .= '</button>';
    $html .= '<button type="button" class="slider-btn slider-next" data-slider-action="next" data-slider-target="' . $slider_id . '">';
    $html .= '<i class="fas fa-chevron-right"></i>';
    $html .= '</button>';
    
    // Indicadores de puntos
    $html .= '<div class="slider-dots">';
    $total_slides = count($marcas_populares);
    $dots_needed = ceil($total_slides / 4); // 4 marcas por slide
    for($i = 0; $i < $dots_needed; $i++) {
        $active_class = ($i === 0) ? ' active' : '';
        $html .= '<span class="dot' . $active_class . '" data-slider-dot="' . $i . '" data-slider-target="' . $slider_id . '"></span>';
    }
    $html .= '</div>';
    
    $html .= '</div>'; // brands-slider-container
    
    // Botón "Ver todas las marcas"
    $html .= '<div style="text-align: center; margin-top: 40px; margin-bottom: 20px;">';
    $html .= '<a href="/listado-marcas" class="btn-modern-outline" style="text-decoration:none; display:inline-flex; align-items:center; gap:10px;">';
    $html .= 'Ver todas las marcas <i class="fas fa-list"></i>';
    $html .= '</a>';
    $html .= '</div>';
    
    $html .= '</div>'; // container
    $html .= '</div>'; // popular-brands-section
    
    return $html;
}

// Función para generar la sección de categorías populares en la home
function generate_popular_categories_section() {
    $categorias = [
        ['nombre' => 'Banca y Cripto', 'slug' => 'banca-y-criptomonedas', 'icon' => 'fas fa-university'],
        ['nombre' => 'Deportes y Nutrición', 'slug' => 'deportes-y-nutricion', 'icon' => 'fas fa-running'],
        ['nombre' => 'Telefonía', 'slug' => 'telefonia-y-comunicaciones', 'icon' => 'fas fa-mobile-alt'],
        ['nombre' => 'Vehículos', 'slug' => 'vehiculos-y-movilidad', 'icon' => 'fas fa-car'],
        ['nombre' => 'Viajes', 'slug' => 'viajes-y-alojamiento', 'icon' => 'fas fa-plane'],
        ['nombre' => 'Alimentación', 'slug' => 'alimentacion-y-gastronomia', 'icon' => 'fas fa-utensils'],
        ['nombre' => 'Plataformas', 'slug' => 'plataformas-y-suscripciones', 'icon' => 'fas fa-tv'],
        ['nombre' => 'Ocio', 'slug' => 'ocio-y-entretenimiento', 'icon' => 'fas fa-gamepad']
    ];
    
    $html = '<div class="popular-categories-section" style="padding: 60px 0; background: #333;">';
    $html .= '<div class="container">';
    $html .= '<div class="section-title h2-style" style="color:#fff; text-align:center; margin-bottom:10px;">Categorías Destacadas</div>';
    $html .= '<p class="section-subtitle" style="color:#ccc; text-align:center; margin-bottom:40px;">Explora los mejores códigos por categoría</p>';
    
    $html .= '<div class="categories-grid" style="display:grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap:20px;">';
    
    foreach ($categorias as $cat) {
        $html .= '<a href="/' . $cat['slug'] . '-comparte-y-gana" class="category-card-modern glass-card animate-on-scroll" style="text-decoration:none;">';
        $html .= '<div class="category-icon" style="font-size: 2rem; color: #E30613; margin-bottom:15px;"><i class="' . $cat['icon'] . '"></i></div>';
        $html .= '<div class="category-name" style="color:#fff; font-weight:700; font-size:1.1rem;">' . $cat['nombre'] . '</div>';
        $html .= '</a>';
    }
    
    $html .= '</div>'; // categories-grid
    
    $html .= '<div style="text-align: center; margin-top: 40px;">';
    $html .= '<a href="/listado-categorias" class="btn-modern-outline" style="text-decoration:none;">Ver todas las categorías</a>';
    $html .= '</div>';
    
    $html .= '</div>'; // container
    $html .= '</div>'; // popular-categories-section
    
    // Estilos inline para las tarjetas de categoría
    $html .= '<style>
    .category-card-modern {
        padding: 30px 20px;
        border-radius: 16px;
        text-align: center;
        transition: all 0.3s ease;
    }
    .category-card-modern:hover {
        transform: translateY(-5px);
        background: #3a3a3a;
        border-color: #E30613;
        box-shadow: 0 10px 20px rgba(0,0,0,0.3);
    }
    .btn-modern-outline {
        border: 2px solid #E30613;
        color: #fff;
        padding: 12px 30px;
        border-radius: 25px;
        font-weight: 600;
        transition: all 0.3s ease;
        background: transparent;
    }
    .btn-modern-outline:hover {
        background: #E30613;
        color: #fff;
        box-shadow: 0 5px 15px rgba(227, 6, 19, 0.3);
    }
    </style>';
    
    return $html;
}

// Función helper para procesar URLs de imagen de marcas
function process_marca_imagen($imagen) {
    if (empty($imagen) || $imagen == 'Sin imagen') {
        return '/img/no_image.png';
    }
    
    // Si no es string, retornar imagen por defecto
    if (!is_string($imagen)) {
        return '/img/no_image.png';
    }
    
    // Si ya es una URL completa válida que comienza con http/https
    if (substr($imagen, 0, 7) === 'http://' || substr($imagen, 0, 8) === 'https://') {
        // Solo convertir http a https si es necesario
        if (strpos($imagen, 'http://') !== false) {
            $imagen = str_replace("http://", "https://", $imagen);
        }
        
        // Convertir URLs de cdn.codigoamigo.com a URLs directas del servidor
        // porque el CDN no está funcionando correctamente
        if (strpos($imagen, 'cdn.codigoamigo.com') !== false) {
            // Extraer el path de la URL del CDN
            $path = parse_url($imagen, PHP_URL_PATH);
            if ($path) {
                // Convertir a URL directa del servidor
                // Si es panel_marcas, necesita /img/ antes
                if (strpos($path, '/panel_marcas/') !== false) {
                    $imagen = 'https://www.codigoamigo.com/img' . $path;
                } else {
                    $imagen = 'https://www.codigoamigo.com' . $path;
                }
            }
        }
        
        return $imagen;
    }
    
    // Si es una ruta relativa (empieza con /), convertirla a URL completa
    if (substr($imagen, 0, 1) === '/') {
        // Para rutas de panel_marcas, mantenerlas directas en el servidor
        if (strpos($imagen, '/img/panel_marcas/') !== false) {
            $imagen = 'https://www.codigoamigo.com' . $imagen;
        } 
        // Para otras rutas en /img/, servir directamente (CloudFront CDN está caído)
        elseif (strpos($imagen, '/img/') !== false) {
            $imagen = 'https://www.codigoamigo.com' . $imagen;
        } else {
            // Otras rutas relativas, añadir dominio
            $imagen = 'https://www.codigoamigo.com' . $imagen;
        }
        return $imagen;
    }
    
    // Si no es ruta absoluta ni relativa, asumir que es una ruta relativa sin /
    return 'https://www.codigoamigo.com/' . ltrim($imagen, '/');
}

// Función para obtener marcas populares para la home
function get_popular_brands_for_home($limit = 9) {
    $marcas_con_info = [];
    
    try {
        $collection_codigos = getCollectionCodigos();
        
        // Pipeline para obtener marcas con más códigos activos
        $pipeline = [
            ['$match' => ['estado' => 0]], // Solo códigos activos
            ['$group' => [
                '_id' => '$marca',
                'total_codigos' => ['$sum' => 1]
            ]],
            ['$sort' => ['total_codigos' => -1]],
            ['$limit' => $limit]
        ];
        
        $marcas_populares = $collection_codigos->aggregate($pipeline)->toArray();
        
        // Obtener información detallada de cada marca
        $collection_marcas = getCollectionMarcas();
        
        foreach($marcas_populares as $marca) {
            // Usar getObjectMarca para obtener la marca con imagen procesada correctamente
            $marca_info = getObjectMarca('nombre_clave', $marca['_id']);
            if($marca_info) {
                // getObjectMarca ya procesa la imagen correctamente
                $imagen = $marca_info['imagen'] ?? '';
                
                // Si la imagen está vacía, es "Sin imagen", o no es válida, usar fallback
                if (empty($imagen) || $imagen === 'Sin imagen' || $imagen === '') {
                    $imagen = '/img/no_image.png';
                }
                
                // Procesar la imagen con la función helper para asegurar URL completa
                $imagen = process_marca_imagen($imagen);
                
                $marcas_con_info[] = [
                    'nombre' => $marca_info['nombre'] ?? $marca['_id'],
                    'nombre_clave' => $marca_info['nombre_clave'] ?? $marca['_id'],
                    'imagen' => $imagen,
                    'categoria' => $marca_info['categoria'] ?? 'General',
                    'total_codigos' => $marca['total_codigos']
                ];
            }
        }
    } catch (Exception $e) {
        // Si hay error con MongoDB, usar datos de respaldo
        log_warning("Error obteniendo marcas populares, usando datos de respaldo", ['error' => $e->getMessage()]);
    }
    
    // Si no se obtuvieron marcas de la base de datos, usar datos de respaldo
    if(empty($marcas_con_info)) {
        $marcas_con_info = get_fallback_popular_brands($limit);
    }
    
    // Procesar imágenes de las marcas de respaldo también
    foreach($marcas_con_info as &$marca) {
        $marca['imagen'] = process_marca_imagen($marca['imagen'] ?? '/img/no_image.png');
    }
    unset($marca);
    
    return $marcas_con_info;
}

/**
 * Genera un slug seguro para una marca cuando no existe nombre_clave.
 */
function generate_brand_slug($brand) {
    if (!is_string($brand)) {
        return '';
    }

    $slug = trim($brand);
    if ($slug === '') {
        return '';
    }

    if (function_exists('mb_strtolower')) {
        $slug = mb_strtolower($slug, 'UTF-8');
    } else {
        $slug = strtolower($slug);
    }

    if (function_exists('iconv')) {
        $transliterated = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $slug);
        if ($transliterated !== false) {
            $slug = $transliterated;
        }
    }

    $slug = preg_replace('/[^a-z0-9]+/i', '-', $slug);
    $slug = trim($slug, '-');

    if ($slug === '') {
        return 'codigo';
    }

    return $slug;
}

// Función de respaldo para obtener marcas populares
function get_fallback_popular_brands($limit = 9) {
    // Cargar el archivo JSON de marcas como respaldo
    $json_file = __DIR__ . '/../datos.json';
    if (!file_exists($json_file)) {
        return get_hardcoded_popular_brands($limit);
    }
    
    $todas_marcas = json_decode(file_get_contents($json_file), true);
    if (!$todas_marcas) {
        return get_hardcoded_popular_brands($limit);
    }
    
    // Filtrar marcas con códigos y ordenar por popularidad
    $marcas_con_codigos = array_filter($todas_marcas, function($marca) {
        return isset($marca['codes']) && $marca['codes'] > 0;
    });
    
    // Ordenar por número de códigos
    usort($marcas_con_codigos, function($a, $b) {
        $codes_a = $a['codes'] ?? 0;
        $codes_b = $b['codes'] ?? 0;
        return $codes_b - $codes_a;
    });
    
    // Tomar las primeras N marcas
    $marcas_seleccionadas = array_slice($marcas_con_codigos, 0, $limit);
    
    $marcas_con_info = [];
    foreach($marcas_seleccionadas as $marca) {
        $marcas_con_info[] = [
            'nombre' => $marca['nombre'] ?? $marca['nombre_clave'] ?? 'Marca',
            'nombre_clave' => $marca['nombre_clave'] ?? '',
            'imagen' => $marca['imagen'] ?? '/img/no_image.png',
            'categoria' => $marca['categoria'] ?? 'General',
            'total_codigos' => $marca['codes'] ?? 0
        ];
    }
    
    return $marcas_con_info;
}

// Función con marcas hardcodeadas como último respaldo
function get_hardcoded_popular_brands($limit = 9) {
    $marcas_hardcoded = [
        [
            'nombre' => 'Amazon',
            'nombre_clave' => 'amazon',
            'imagen' => 'https://www.codigoamigo.com/img/marcas/amazon.png',
            'categoria' => 'Compras',
            'total_codigos' => 15
        ],
        [
            'nombre' => 'Netflix',
            'nombre_clave' => 'netflix',
            'imagen' => 'https://www.codigoamigo.com/img/marcas/netflix.png',
            'categoria' => 'Entretenimiento',
            'total_codigos' => 12
        ],
        [
            'nombre' => 'Spotify',
            'nombre_clave' => 'spotify',
            'imagen' => 'https://www.codigoamigo.com/img/marcas/spotify.png',
            'categoria' => 'Música',
            'total_codigos' => 10
        ],
        [
            'nombre' => 'Uber',
            'nombre_clave' => 'uber',
            'imagen' => 'https://www.codigoamigo.com/img/marcas/uber.png',
            'categoria' => 'Transporte',
            'total_codigos' => 8
        ],
        [
            'nombre' => 'Airbnb',
            'nombre_clave' => 'airbnb',
            'imagen' => 'https://www.codigoamigo.com/img/marcas/airbnb.png',
            'categoria' => 'Viajes',
            'total_codigos' => 7
        ]
    ];
    
    return array_slice($marcas_hardcoded, 0, $limit);
}

// Función para generar el menú de filtros del chollómetro
function generate_chollometro_filter_menu($marca) {
    // Obtener el filtro de fecha actual de la URL
    $fecha_actual = isset($_GET['fecha']) ? $_GET['fecha'] : 'hoy';
    $tipo_actual = isset($_GET['tipo']) ? $_GET['tipo'] : 'todos';
    
    $html = '<div class="chollometro-filter-container">';
    
    // Barra de filtros principal
    $html .= '<div class="filter-bar">';
    
    // Botón "Más" (hamburger menu)
    $html .= '<button class="filter-btn filter-more-btn" id="filter-more-btn">';
    $html .= '<i class="fas fa-bars"></i>';
    $html .= '<span>Más</span>';
    $html .= '</button>';
    
    // Botones de filtro tipo Chollometro
    $html .= '<div class="filter-buttons">';
    
    // Botón "TODOS"
    $active_class = ($tipo_actual === 'todos') ? ' active' : '';
    $html .= '<button class="filter-btn filter-type-btn' . $active_class . '" data-type="todos">';
    $html .= 'TODOS (36)';
    $html .= '</button>';
    
    // Botón "CUPONES"
    $active_class = ($tipo_actual === 'cupones') ? ' active' : '';
    $html .= '<button class="filter-btn filter-type-btn' . $active_class . '" data-type="cupones">';
    $html .= 'CUPONES (21)';
    $html .= '</button>';
    
    // Botón "DESCUENTOS"
    $active_class = ($tipo_actual === 'descuentos') ? ' active' : '';
    $html .= '<button class="filter-btn filter-type-btn' . $active_class . '" data-type="descuentos">';
    $html .= 'DESCUENTOS (15)';
    $html .= '</button>';
    
    $html .= '</div>'; // filter-buttons
    
    // Botón de búsqueda (lupa)
    $html .= '<button class="filter-btn filter-search-btn" id="filter-search-btn">';
    $html .= '<i class="fas fa-search"></i>';
    $html .= '</button>';
    
    $html .= '</div>'; // filter-bar
    
    // Panel de filtros desplegable (Más)
    $html .= '<div class="filter-panel" id="filter-panel">';
    $html .= '<div class="filter-panel-content">';
    $html .= '<h4>Filtros</h4>';
    
    // Filtro por fecha
    $html .= '<div class="filter-group">';
    $html .= '<h5>Por fecha</h5>';
    
    $checked_hoy = ($fecha_actual === 'hoy') ? ' checked' : '';
    $html .= '<label class="filter-option">';
    $html .= '<input type="radio" name="fecha" value="hoy"' . $checked_hoy . '>';
    $html .= '<span>Publicados hoy</span>';
    $html .= '</label>';
    
    $checked_semana = ($fecha_actual === 'semana') ? ' checked' : '';
    $html .= '<label class="filter-option">';
    $html .= '<input type="radio" name="fecha" value="semana"' . $checked_semana . '>';
    $html .= '<span>Publicados la semana pasada</span>';
    $html .= '</label>';
    
    $checked_todo = ($fecha_actual === 'todo') ? ' checked' : '';
    $html .= '<label class="filter-option">';
    $html .= '<input type="radio" name="fecha" value="todo"' . $checked_todo . '>';
    $html .= '<span>Publicados todo el tiempo</span>';
    $html .= '</label>';
    
    $html .= '</div>'; // filter-group
    
    // Botones de acción
    $html .= '<div class="filter-actions">';
    $html .= '<button class="btn-apply" onclick="applyFilters()">Aplicar filtros</button>';
    $html .= '<button class="btn-clear" onclick="clearFilters()">Limpiar filtros</button>';
    $html .= '</div>';
    
    $html .= '</div>'; // filter-panel-content
    $html .= '</div>'; // filter-panel
    
    // Panel de búsqueda desplegable
    $html .= '<div class="search-panel" id="search-panel">';
    $html .= '<div class="search-panel-content">';
    $html .= '<form class="search-form" onsubmit="performSearch(event)">';
    $html .= '<input type="text" class="search-input" placeholder="Buscar códigos..." value="' . htmlspecialchars(isset($_GET['q']) ? $_GET['q'] : '') . '">';
    $html .= '<button type="submit" class="search-submit">';
    $html .= '<i class="fas fa-search"></i>';
    $html .= '</button>';
    $html .= '</form>';
    $html .= '</div>'; // search-panel-content
    $html .= '</div>'; // search-panel
    
    $html .= '</div>'; // chollometro-filter-container
    
    return $html;
}

function generate_modern_code_cards($lista_codigos) {
    $html = '';
    
    if(empty($lista_codigos)) {
        $html .= '<div style="text-align: center; color: #ccc; padding: 2rem;">';
        $html .= '<i class="fas fa-search" style="font-size: 3rem; margin-bottom: 1rem; color: #E30613;"></i>';
        $html .= '<h3>No se encontraron códigos</h3>';
        $html .= '<p>Intenta con otros términos de búsqueda</p>';
        $html .= '</div>';
        return $html;
    }
    
    foreach($lista_codigos as $codigo) {
        $html .= generate_single_code_card($codigo);
    }
    
    return $html;
}

/**
 * Genera un slider con tarjetas de códigos modernos
 */
function generate_modern_codes_slider($lista_codigos, $nombre_marca = '') {
    if(empty($lista_codigos)) {
        return '';
    }

    static $codes_slider_counter = 0;
    $slider_id = 'codesSlider-' . (++$codes_slider_counter);

    $html = '<div class="featured-codes-slider-container" data-slider-root data-slider-id="' . $slider_id . '" data-slides-desktop="3" data-slides-tablet="2" data-slides-mobile="1.1">';
    $html .= '<div class="featured-codes-slider" id="' . $slider_id . '" data-slider-track>';

    foreach($lista_codigos as $codigo) {
        $html .= '<div class="featured-code-slide" data-slider-item>';
        $html .= generate_single_code_card($codigo);
        $html .= '</div>';
    }

    $html .= '</div>'; // featured-codes-slider

    // Controles del slider
    $html .= '<button type="button" class="slider-btn slider-prev" data-slider-action="prev" data-slider-target="' . $slider_id . '">';
    $html .= '<i class="fas fa-chevron-left"></i>';
    $html .= '</button>';
    $html .= '<button type="button" class="slider-btn slider-next" data-slider-action="next" data-slider-target="' . $slider_id . '">';
    $html .= '<i class="fas fa-chevron-right"></i>';
    $html .= '</button>';

    // Indicadores de puntos
    $html .= '<div class="slider-dots">';
    $total_slides = count($lista_codigos);
    $slides_per_view = 3; 
    $dots_needed = ceil($total_slides / $slides_per_view);
    for($i = 0; $i < $dots_needed; $i++) {
        $active_class = ($i === 0) ? ' active' : '';
        $html .= '<span class="dot' . $active_class . '" data-slider-dot="' . $i . '" data-slider-target="' . $slider_id . '"></span>';
    }
    $html .= '</div>';

    $html .= '</div>'; // featured-codes-slider-container
    
    return $html;
}

// Función para generar una tarjeta de código individual
function generate_single_code_card($codigo) {
    $brand = isset($codigo['marca']) ? $codigo['marca'] : 'Marca desconocida';
    $description = isset($codigo['descripcion']) ? $codigo['descripcion'] : 'Descripción no disponible';
    $code_id = isset($codigo['_id']) ? (string)$codigo['_id'] : '';
    $benefit = isset($codigo['num_beneficio']) ? $codigo['num_beneficio'] : 0;
    $usuario_id = isset($codigo['id_usuario']) ? $codigo['id_usuario'] : '';
    
    // Obtener información del usuario
    $user_info = get_user_info($usuario_id);
    $username = $user_info['username'];
    $user_img = $user_info['img'];
    
    // Verificar si es VIP
    if (!function_exists('es_usuario_vip')) {
        include_once __DIR__ . '/funciones_usuario.php';
    }
    $es_vip = es_usuario_vip($usuario_id);
    $vip_class = $es_vip ? ' vip-user' : '';
    
    // Obtener información de la marca para el logo
    $marca_info = get_brand_info($brand);
    $marca_imagen = $marca_info['imagen'] ?? '';
    // Corregir slug de marca
    if (!function_exists('generate_brand_slug')) {
        include_once __DIR__ . '/funciones_modern.php';
    }
    $brand_slug = $marca_info['nombre_clave'] ?? generate_brand_slug($brand);
    
    // Limpiar descripción
    $description = strip_tags($description);
    $description = mb_substr($description, 0, 150) . (mb_strlen($description) > 150 ? '...' : '');
    
    // Crear enlace a la página de la marca
    $marca_url = '/de-' . $brand_slug;
    
    $html = '<div class="code-card glass-card animate-on-scroll' . $vip_class . '" data-code-id="' . htmlspecialchars($code_id) . '">';
    
    // Contenedor flip card
    $html .= '<div class="flip-card">';
    $html .= '<div class="flip-card-inner">';
    
    // Front side (Logo and badges)
    $html .= '<div class="flip-card-front">';
    // Imagen de la marca (clickeable)
    $html .= '<div class="code-brand-image">';
    $html .= '<a href="' . htmlspecialchars($marca_url) . '" class="brand-link" title="Códigos descuento ' . htmlspecialchars($brand) . '">';
    if($marca_imagen) {
        $html .= '<img loading="lazy" src="' . htmlspecialchars($marca_imagen) . '" alt="' . htmlspecialchars($brand) . '" class="brand-image">';
    } else {
        $html .= '<div class="brand-placeholder">';
        $html .= '<i class="fas fa-tag"></i>';
        $html .= '</div>';
    }
    $html .= '</a>';
    // Badge flotante con nombre de marca
    $html .= '<div class="brand-name-badge">';
    $html .= htmlspecialchars($brand);
    $html .= '</div>';
    // Badge flotante con fecha (si existe)
    if(isset($codigo['fecha_publicacion'])) {
        $fecha_formateada = formatDateAgoLarge($codigo['fecha_publicacion']);
        $html .= '<div class="brand-date-badge">';
        $html .= '<i class="far fa-clock"></i>';
        $html .= '<span>' . htmlspecialchars($fecha_formateada) . '</span>';
        $html .= '</div>';
    }
    $html .= '</div>'; // .code-brand-image
    $html .= '</div>'; // .flip-card-front
    
    // Back side (Description)
    $brand_desc = $marca_info['descripcion'] ?? $marca_info['seo_que_es'] ?? 'Información sobre ' . htmlspecialchars($brand) . ' no disponible';
    // Limpiar HTML y decodificar entidades
    $brand_desc = strip_tags($brand_desc);
    $brand_desc = html_entity_decode($brand_desc, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $brand_desc_short = strlen($brand_desc) > 180 ? substr($brand_desc, 0, 177) . '...' : $brand_desc;
    
    $html .= '<div class="flip-card-back">';
    $html .= '<div class="brand-info-back">';
    $html .= '<h4>¿Qué es ' . htmlspecialchars($brand) . '?</h4>';
    $html .= '<p>' . $brand_desc_short . '</p>';
    $html .= '<a href="' . htmlspecialchars($marca_url) . '" class="btn-more-info">Ver más códigos <i class="fas fa-arrow-right"></i></a>';
    $html .= '</div>';
    $html .= '</div>'; // .flip-card-back
    
    $html .= '</div>'; // .flip-card-inner
    
    // Icono "?" para móvil
    $html .= '<div class="flip-trigger-mobile" title="Saber más sobre esta marca"><i class="fas fa-question-circle"></i></div>';
    
    $html .= '</div>'; // .flip-card
    
    // Obtener el link del usuario
    $username_url = str_replace(' ', '+', $username);
    $user_hash = isset($user_info['hash']) ? $user_info['hash'] : substr(md5($username), 0, 24);
    if (isset($user_info['id'])) {
        if (is_object($user_info['id']) && method_exists($user_info['id'], '__toString')) {
            $user_id = (string)$user_info['id'];
        } else {
            $user_id = $user_info['id'];
        }
    } else {
        $user_id = null;
    }
    $user_link = link_usuario($username, $user_id ?? null);
    
    // Descripción con formato de chat
    $html .= '<div class="code-description">';
    $html .= '<div class="code-description-author">';
    // Avatar del usuario
    $html .= '<div class="code-user-avatar-small">';
    if($user_img) {
        $html .= '<img src="' . htmlspecialchars($user_img) . '" alt="Avatar de ' . htmlspecialchars($username) . '" class="code-user-img-small">';
    } else {
        $html .= '<div class="code-user-placeholder-small">';
        if(isset($user_info['iniciales']) && !empty($user_info['iniciales'])) {
            $html .= '<span class="user-iniciales-small">' . htmlspecialchars($user_info['iniciales']) . '</span>';
        } else {
            $html .= '<i class="fas fa-user"></i>';
        }
        $html .= '</div>';
    }
    $html .= '</div>';
    $html .= '<a href="' . htmlspecialchars($user_link) . '" class="code-user-name-link">' . htmlspecialchars($username) . '</a>';
    if ($es_vip) {
        $html .= '<span class="vip-badge-gold" style="margin-left: 8px;" title="Usuario VIP Verificado"><i class="fas fa-crown"></i> VIP</span>';
    }
    $html .= '</div>';
    $html .= '<div class="code-description-text">' . htmlspecialchars($description) . '</div>';

    // Stats row: clicks, votos, antigüedad publicador
    $totalclicks = isset($codigo['totalclicks']) ? (int)$codigo['totalclicks'] : 0;
    $votos_pos = isset($codigo['votos_positivos']) ? (int)$codigo['votos_positivos'] : 0;

    // Antigüedad publicador desde ObjectId (primeros 4 bytes = unix timestamp)
    $anio_registro = null;
    if (!empty($usuario_id)) {
        try {
            $uid_str = is_object($usuario_id) ? (string)$usuario_id : $usuario_id;
            if (strlen($uid_str) === 24) {
                $ts_usuario = hexdec(substr($uid_str, 0, 8));
                $anio_registro = date('Y', $ts_usuario);
            }
        } catch (Exception $e) {}
    }

    $html .= '<div class="code-stats-row" style="display:flex;gap:10px;flex-wrap:wrap;margin-top:6px;font-size:12px;">';
    if ($totalclicks > 0) {
        $clicks_fmt = $totalclicks >= 1000 ? round($totalclicks/1000, 1) . 'K' : $totalclicks;
        $html .= '<span style="color:#27ae60;font-weight:600;" title="Personas que usaron este código"><i class="fas fa-users"></i> ' . $clicks_fmt . ' personas lo usaron</span>';
    }
    if ($votos_pos > 0) {
        $html .= '<span style="color:#2980b9;" title="Valoraciones positivas"><i class="fas fa-thumbs-up"></i> ' . number_format($votos_pos, 0, ',', '.') . '</span>';
    }
    if ($anio_registro) {
        $html .= '<span style="color:#888;" title="Antigüedad del publicador"><i class="far fa-calendar-check"></i> desde ' . $anio_registro . '</span>';
    }
    $html .= '</div>';

    // Fecha de publicación visible
    if(isset($codigo['fecha_publicacion'])) {
        $fecha_pub = formatDateAgoLarge($codigo['fecha_publicacion']);
        $html .= '<div class="code-publish-date"><i class="far fa-calendar-alt"></i> Publicado ' . htmlspecialchars($fecha_pub) . '</div>';
    }
    $html .= '</div>';
    
    // Información adicional - Solo Beneficio (la fecha ya está en el logo)
    if($benefit > 0) {
        // Incluir funciones premium si no están incluidas
        if (!function_exists('generarHTMLPrecioConPromocion')) {
            include_once __DIR__ . '/funciones_premium.php';
        }
        
        $html .= '<div class="code-meta-info">';
        $html .= '<div class="beneficio-destacado">';
        $html .= '<div class="beneficio-icono">💰</div>';
        $html .= '<div class="beneficio-contenido">';
        
        // Obtener tipo de descuento del código
        $tipo_descuento = isset($codigo['tipo_descuento']) ? $codigo['tipo_descuento'] : 'euros';
        
        // Mostrar precio con promoción si existe
        $html .= generarHTMLPrecioConPromocion($code_id, $benefit, $tipo_descuento, false);
        
        $html .= '<div class="beneficio-tipo">BENEFICIO</div>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
    }
    
    // Botones de acción
    $html .= '<div class="code-actions">';
    
    // Botón de acción principal
    $html .= '<button class="code-button" onclick="viewCode(\'' . htmlspecialchars($code_id) . '\', \'' . htmlspecialchars($brand_slug) . '\')">';
    $html .= '<i class="fas fa-eye"></i> Ver Código';
    $html .= '</button>';
    
    // Botón de chat directo para usuarios VIP
    if ($es_vip && !empty($user_id)) {
        $current_user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : '';
        // No mostrar el botón de chat en la propia tarjeta del usuario logueado
        if ((string)$current_user_id !== (string)$user_id) {
            $chat_user_id = htmlspecialchars((string)$user_id);
            $chat_username = htmlspecialchars($username);
            $html .= '<button class="code-button-chat" onclick="openDirectChat(\'' . $chat_user_id . '\', \'' . $chat_username . '\')">';
            $html .= '<i class="fas fa-comments"></i> Contactar';
            $html .= '</button>';
        }
    }
    
    $html .= '</div>';
    
    $html .= '</div>';
    
    return $html;
}


// Función para obtener CSS adicional moderno
function get_modern_additional_css() {
    return '
    <style>
    :root {
        --premium-glass: rgba(255, 255, 255, 0.03);
        --premium-glass-hover: rgba(255, 255, 255, 0.07);
        --premium-border: rgba(255, 255, 255, 0.08);
        --premium-border-hover: rgba(255, 255, 255, 0.15);
        --premium-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
        --premium-blur: blur(10px);
        --deep-dark: #0f0f0f;
        --accent-glow: 0 0 15px rgba(227, 6, 19, 0.2);
    }

    /* Base Body Refinement */
    body {
        background-color: var(--deep-dark) !important;
        color: #e0e0e0 !important;
    }

    /* Glassmorphism Class */
    .glass-card {
        background: var(--premium-glass) !important;
        backdrop-filter: var(--premium-blur);
        -webkit-backdrop-filter: var(--premium-blur);
        border: 1px solid var(--premium-border) !important;
        box-shadow: var(--premium-shadow);
        transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
    }

    .glass-card:hover {
        background: var(--premium-glass-hover) !important;
        border-color: var(--premium-border-hover) !important;
        transform: translateY(-8px) scale(1.01);
        box-shadow: 0 15px 45px rgba(0,0,0,0.5), var(--accent-glow);
    }

    /* Premium Animations - Visible by default for safety */
    .animate-on-scroll {
        opacity: 1;
        transform: translateY(0);
        transition: opacity 0.8s ease-out, transform 0.8s cubic-bezier(0.165, 0.84, 0.44, 1);
    }

    /* Only hide if JS is actively going to animate them */
    .js-ready .animate-on-scroll:not(.is-visible) {
        opacity: 0;
        transform: translateY(30px);
    }

    /* Estilos para el sistema de encabezados jerárquicos */
    .page-header {
        text-align: center;
        margin-bottom: 3rem;
        padding: 4rem 2rem;
        background: linear-gradient(135deg, #1a1a1a 0%, #000 100%);
        color: white;
        border-radius: 24px;
        position: relative;
        overflow: hidden;
        border: 1px solid var(--premium-border);
        box-shadow: 0 10px 40px rgba(0,0,0,0.5);
    }
    
    .page-header::before {
        content: "";
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(227, 6, 19, 0.05) 0%, transparent 70%);
        pointer-events: none;
    }

    .page-header h1 {
        font-size: 3rem;
        font-weight: bold;
        margin-bottom: 1rem;
        text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
    }

    .page-subtitle {
        font-size: 1.3rem;
        opacity: 0.9;
        margin: 0;
        line-height: 1.5;
    }

    .main-section {
        margin: 3rem 0;
        padding: 2rem;
        background: rgba(255, 255, 255, 0.05);
        border-radius: 15px;
        border-left: 5px solid var(--primary-orange);
    }

    .main-section h2 {
        color: var(--primary-orange);
        font-size: 2.2rem;
        font-weight: bold;
        margin-bottom: 1.5rem;
        position: relative;
    }

    .main-section h2:before {
        content: "";
        position: absolute;
        left: -2rem;
        top: 50%;
        transform: translateY(-50%);
        width: 8px;
        height: 60%;
        background: var(--primary-orange);
        border-radius: 4px;
    }

    .subsection {
        margin: 2rem 0;
        padding: 1.5rem;
        background: rgba(255, 255, 255, 0.02);
        border-radius: 10px;
        border-left: 3px solid rgba(227, 6, 19, 0.5);
    }

    .subsection h3 {
        color: rgba(255, 255, 255, 0.9);
        font-size: 1.5rem;
        font-weight: 600;
        margin-bottom: 1rem;
    }

    .sub-subsection {
        margin: 1.5rem 0;
        padding: 1rem;
        background: rgba(255, 255, 255, 0.01);
        border-radius: 8px;
    }

    .sub-subsection h4 {
        color: rgba(255, 255, 255, 0.8);
        font-size: 1.2rem;
        font-weight: 500;
        margin-bottom: 0.5rem;
    }

    .faq-section {
        margin: 3rem 0;
        padding: 2rem;
        background: rgba(255, 255, 255, 0.03);
        border-radius: 15px;
    }

    .faq-section h2 {
        color: var(--primary-orange);
        text-align: center;
        margin-bottom: 2rem;
        font-size: 2rem;
    }

    .faq-list {
        max-width: 800px;
        margin: 0 auto;
    }

    .faq-item {
        margin-bottom: 1.5rem;
        padding: 1.5rem;
        background: rgba(255, 255, 255, 0.05);
        border-radius: 10px;
        border-left: 4px solid var(--primary-orange);
    }

    .faq-question {
        color: var(--primary-orange) !important;
        font-size: 1.3rem !important;
        margin-bottom: 1rem !important;
        cursor: pointer;
        transition: color 0.3s ease;
    }

    .faq-question:hover {
        color: #ff8c5a !important;
    }

    .faq-answer {
        color: rgba(255, 255, 255, 0.8);
        line-height: 1.6;
        padding-left: 1rem;
    }

    .page-navigation {
        margin: 3rem 0;
        padding: 2rem;
        background: rgba(255, 255, 255, 0.02);
        border-radius: 15px;
        text-align: center;
    }

    .page-navigation h2 {
        color: var(--primary-orange);
        margin-bottom: 2rem;
        font-size: 1.8rem;
    }

    .nav-list {
        list-style: none;
        padding: 0;
        margin: 0;
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 1rem;
    }

    .nav-item {
        margin: 0;
    }

    .nav-link {
        display: block;
        padding: 0.75rem 1.5rem;
        background: rgba(255, 255, 255, 0.1);
        color: rgba(255, 255, 255, 0.9);
        text-decoration: none;
        border-radius: 25px;
        font-weight: 500;
        transition: all 0.3s ease;
        border: 2px solid transparent;
    }

    .nav-link:hover {
        background: var(--primary-orange);
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(227, 6, 19, 0.3);
    }

    /* Featured section styles */
    .featured-section {
        margin: 3rem 0;
        padding: 2rem 0;
        background: linear-gradient(135deg, var(--dark-gray) 0%, #1A1A1A 100%);
        border-radius: 20px;
    }

    .featured-title {
        text-align: center !important;
        margin-bottom: 2rem !important;
    }

    .featured-title h2 {
        color: var(--primary-orange) !important;
        font-size: 2.5rem !important;
        margin-bottom: 0 !important;
    }

    .featured-title h2:before {
        display: none !important;
    }
    
    .featured-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 2rem;
        margin-top: 2rem;
    }
    
    .featured-card {
        background: #2a2a2a;
        border-radius: 16px;
        padding: 1.25rem;
        position: relative;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.25);
        transition: transform 0.25s ease, box-shadow 0.25s ease;
        border: 1px solid rgba(255,255,255,0.06);
        color: var(--text-white);
        display: flex;
        flex-direction: column;
        gap: .9rem;
    }
    
    .featured-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 30px rgba(0,0,0,0.35);
    }
    
    .featured-badge {
        position: absolute;
        top: 12px;
        right: 12px;
        background: linear-gradient(135deg, var(--primary-orange), #FF4D4D) !important;
        color: #fff !important;
        padding: 0.35rem .75rem;
        border-radius: 999px !important;
        font-weight: 700;
        font-size: .75rem;
        box-shadow: 0 6px 16px rgba(255,107,53,0.35);
        z-index: 15;
    }
    
    .featured-brand-logo {
        height: 200px !important;
        min-height: 200px !important;
    }
    
    .featured-brand-logo .brand-link {
        display: block;
        width: 100%;
        height: 100%;
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        text-decoration: none;
    }
    
    .featured-brand-logo .brand-link:hover {
        transform: none;
    }
    
    .featured-brand-logo .brand-logo-img {
        width: 100% !important;
        height: 100% !important;
        object-fit: cover !important;
        object-position: center !important;
        display: block !important;
        border-radius: 0;
        margin: 0;
        padding: 0;
    }
    
    .featured-brand-logo .brand-logo-placeholder {
        width: 100% !important;
        height: 100% !important;
        background: #FFFFFF;
        border-radius: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--primary-orange);
        font-size: 4rem;
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        margin: 0;
        padding: 0;
    }
    
    .featured-brand {
        font-size: 1.5rem;
        font-weight: bold;
        color: var(--text-white);
        margin-bottom: 1rem;
        text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
        text-align: center;
    }
    
    .featured-description {
        color: var(--text-gray);
        line-height: 1.55;
        font-size: 1rem;
        background: rgba(255,255,255,0.03);
        border: 1px solid rgba(255,255,255,0.06);
        padding: .75rem .9rem;
        border-radius: 10px;
    }
    
    .featured-stats {
        display: flex;
        justify-content: space-between;
        margin-bottom: 1.5rem;
        gap: 1rem;
    }
    
    .featured-stat {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        color: rgba(255, 255, 255, 0.8);
        font-size: 0.9rem;
        font-weight: 500;
    }
    
    .featured-stat i {
        color: var(--text-white);
    }
    
    .featured-button {
        background: linear-gradient(135deg, var(--primary-orange), #FF4D4D);
        color: #fff;
        border: none;
        border-radius: 12px;
        padding: .9rem 1.2rem;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.2s ease;
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: .5rem;
        font-size: 1rem;
        box-shadow: 0 6px 16px rgba(255,107,53,0.25);
    }
    
    .featured-button:hover {
        filter: brightness(1.03);
        transform: translateY(-1px);
        box-shadow: 0 10px 20px rgba(255,107,53,0.35);
    }
    
    .pagination-modern {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 1rem;
        margin: 2rem 0;
    }
    
    .pagination-modern .pagination-info {
        font-size: 0.9rem;
        color: rgba(255, 255, 255, 0.8);
        text-align: center;
    }
    
    .pagination-modern .pagination-controls {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 0.25rem;
        flex-wrap: wrap;
    }
    
    .pagination-modern .pagination-btn {
        background: transparent;
        color: var(--text-white);
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 4px;
        padding: 0.5rem 0.75rem;
        text-decoration: none;
        transition: all 0.2s ease;
        font-weight: 400;
        font-size: 0.9rem;
        min-width: 40px;
        text-align: center;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.25rem;
    }
    
    .pagination-modern .pagination-btn:hover:not(.disabled):not(.active) {
        background: rgba(255, 255, 255, 0.1);
        border-color: rgba(255, 255, 255, 0.3);
    }
    
    .pagination-modern .pagination-btn.active {
        background: var(--primary-orange);
        color: var(--text-white);
        border-color: var(--primary-orange);
        font-weight: 500;
    }
    
    .pagination-modern .pagination-btn.disabled {
        opacity: 0.3;
        cursor: not-allowed;
        pointer-events: none;
    }
    
    .pagination-modern .pagination-ellipsis {
        color: rgba(255, 255, 255, 0.6);
        padding: 0.5rem 0.5rem;
        font-size: 0.9rem;
    }
    
    .pagination-modern .pagination-arrow {
        font-size: 1.2rem;
        line-height: 1;
    }
    
    .pagination-modern .pagination-prev,
    .pagination-modern .pagination-next {
        padding: 0.5rem 1rem;
    }
    
    .pagination-modern .pagination-number {
        min-width: 36px;
    }
    
    .categories-modern {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        margin: 2rem 0;
    }
    
    .category-card {
        background: var(--light-gray);
        border-radius: 15px;
        padding: 1.5rem;
        text-align: center;
        transition: all 0.3s ease;
        border: 2px solid transparent;
    }
    
    .category-card:hover {
        border-color: var(--primary-orange);
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(227, 6, 19, 0.3);
    }
    
    .category-icon {
        font-size: 2rem;
        color: var(--primary-orange);
        margin-bottom: 1rem;
    }
    
    .category-name {
        font-size: 1.1rem;
        font-weight: bold;
        color: var(--text-white);
        margin-bottom: 0.5rem;
    }
    
    .category-count {
        color: rgba(255, 255, 255, 0.7);
        font-size: 0.9rem;
    }
    
    @media (max-width: 768px) {
        .page-header {
            padding: 2rem 1rem;
            margin-bottom: 1.5rem;
            border-radius: 16px;
        }

        .premium-h1 {
            font-size: 1.8rem !important;
        }

        .premium-subtitle {
            font-size: 1rem !important;
        }

        .breadcrumb-container {
            margin-top: 10px !important;
        }

        .breadcrumb-nav {
            margin-bottom: 15px !important;
            font-size: 0.8rem;
        }

        .featured-grid {
            grid-template-columns: 1fr;
        }

        .categories-modern {
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        }

        .pagination-modern {
            flex-wrap: wrap;
        }
    }

    </style>';
}

// Función para obtener información de una categoría
function get_category_info($categoria_url) {
    $categorias_info = [
        // Categorías reales de la base de datos
        'alimentacion-y-gastronomia' => [
            'nombre' => 'Alimentación y Gastronomía',
            'descripcion' => 'Descubre los mejores códigos de descuento en restaurantes, supermercados, comida a domicilio y productos gastronómicos.',
            'icono' => 'fas fa-utensils'
        ],
        'banca-y-criptomonedas' => [
            'nombre' => 'Banca y Criptomonedas',
            'descripcion' => 'Códigos para servicios bancarios, criptomonedas, inversiones y productos financieros.',
            'icono' => 'fas fa-credit-card'
        ],
        'deportes-y-nutricion' => [
            'nombre' => 'Deportes y Nutrición',
            'descripcion' => 'Descuentos en equipamiento deportivo, gimnasios, nutrición y actividades físicas.',
            'icono' => 'fas fa-dumbbell'
        ],
        'cursos' => [
            'nombre' => 'Cursos',
            'descripcion' => 'Plataformas de formación online, cursos especializados y educación digital.',
            'icono' => 'fas fa-graduation-cap'
        ],
        'club-de-compras' => [
            'nombre' => 'Club de Compras',
            'descripcion' => 'Programas de fidelización, cashback y clubes exclusivos de compras.',
            'icono' => 'fas fa-shopping-cart'
        ],
        'telefonia-y-comunicaciones' => [
            'nombre' => 'Telefonía y Comunicaciones',
            'descripcion' => 'Servicios de telefonía móvil, fija, internet y comunicaciones digitales.',
            'icono' => 'fas fa-mobile-alt'
        ],
        'vehiculos-y-movilidad' => [
            'nombre' => 'Vehículos y Movilidad',
            'descripcion' => 'Códigos para vehículos, movilidad urbana y servicios de transporte.',
            'icono' => 'fas fa-car'
        ],
        'apuestas' => [
            'nombre' => 'Apuestas',
            'descripcion' => 'Casas de apuestas deportivas, casinos online y juegos de azar.',
            'icono' => 'fas fa-dice'
        ],
        'herramientas' => [
            'nombre' => 'Herramientas',
            'descripcion' => 'Herramientas profesionales, software y servicios para empresas.',
            'icono' => 'fas fa-tools'
        ],
        'viajes-y-alojamiento' => [
            'nombre' => 'Viajes y Alojamiento',
            'descripcion' => 'Ahorra en vuelos, hoteles, alquiler de coches, paquetes turísticos y experiencias de viaje.',
            'icono' => 'fas fa-plane'
        ],
        'seguros' => [
            'nombre' => 'Seguros',
            'descripcion' => 'Seguros de vida, hogar, vehículo, salud y protección financiera.',
            'icono' => 'fas fa-shield-alt'
        ],
        'suministros-y-servicios' => [
            'nombre' => 'Suministros y Servicios',
            'descripcion' => 'Servicios esenciales, suministros industriales y servicios profesionales.',
            'icono' => 'fas fa-cogs'
        ],
        'mascota' => [
            'nombre' => 'Mascota',
            'descripcion' => 'Productos y servicios para el cuidado y bienestar de las mascotas.',
            'icono' => 'fas fa-paw'
        ],
        'mensajeria' => [
            'nombre' => 'Mensajería',
            'descripcion' => 'Servicios de envío, paquetería y logística para empresas y particulares.',
            'icono' => 'fas fa-shipping-fast'
        ],
        'inteligencia-artificial' => [
            'nombre' => 'Inteligencia Artificial',
            'descripcion' => 'Herramientas y servicios de IA, machine learning y automatización.',
            'icono' => 'fas fa-robot'
        ],
        'plataformas-y-suscripciones' => [
            'nombre' => 'Plataformas y Suscripciones',
            'descripcion' => 'Servicios de streaming, software como servicio y suscripciones digitales.',
            'icono' => 'fas fa-layer-group'
        ],
        'ocio-y-entretenimiento' => [
            'nombre' => 'Ocio y Entretenimiento',
            'descripcion' => 'Actividades de ocio, entretenimiento, eventos y experiencias culturales.',
            'icono' => 'fas fa-gamepad'
        ],
        'select' => [
            'nombre' => 'General',
            'descripcion' => 'Códigos de descuento generales y ofertas variadas.',
            'icono' => 'fas fa-tag'
        ],
        // Categorías antiguas (mantenidas por compatibilidad)
        'alimentacion-y-gastronomia-comparte-y-gana' => [
            'nombre' => 'Alimentación y Gastronomía',
            'descripcion' => 'Descubre los mejores códigos de descuento en restaurantes, supermercados, comida a domicilio y productos gastronómicos.',
            'icono' => 'fas fa-utensils'
        ],
        'tecnologia-y-electronica-comparte-y-gana' => [
            'nombre' => 'Tecnología y Electrónica',
            'descripcion' => 'Ahorra en dispositivos electrónicos, smartphones, ordenadores, accesorios tecnológicos y gadgets.',
            'icono' => 'fas fa-laptop'
        ],
        'moda-y-belleza-comparte-y-gana' => [
            'nombre' => 'Moda y Belleza',
            'descripcion' => 'Encuentra descuentos en ropa, calzado, cosméticos, productos de belleza y accesorios de moda.',
            'icono' => 'fas fa-tshirt'
        ],
        'hogar-y-jardin-comparte-y-gana' => [
            'nombre' => 'Hogar y Jardín',
            'descripcion' => 'Ahorra en muebles, decoración, electrodomésticos, herramientas de jardín y productos para el hogar.',
            'icono' => 'fas fa-home'
        ],
        'deportes-y-ocio-comparte-y-gana' => [
            'nombre' => 'Deportes y Ocio',
            'descripcion' => 'Descuentos en equipamiento deportivo, gimnasios, actividades de ocio y entretenimiento.',
            'icono' => 'fas fa-dumbbell'
        ],
        'viajes-y-turismo-comparte-y-gana' => [
            'nombre' => 'Viajes y Turismo',
            'descripcion' => 'Ahorra en vuelos, hoteles, alquiler de coches, paquetes turísticos y experiencias de viaje.',
            'icono' => 'fas fa-plane'
        ],
        'finanzas-y-seguros-comparte-y-gana' => [
            'nombre' => 'Finanzas y Seguros',
            'descripcion' => 'Códigos para servicios bancarios, seguros, inversiones y productos financieros.',
            'icono' => 'fas fa-credit-card'
        ],
        'salud-y-bienestar-comparte-y-gana' => [
            'nombre' => 'Salud y Bienestar',
            'descripcion' => 'Descuentos en farmacias, productos de salud, bienestar, fitness y cuidado personal.',
            'icono' => 'fas fa-heart'
        ]
    ];
    
    return $categorias_info[$categoria_url] ?? [
        'nombre' => 'Categoría',
        'descripcion' => 'Descubre los mejores códigos de descuento en esta categoría.',
        'icono' => 'fas fa-tag'
    ];
}

// Función para obtener marcas de una categoría específica
function get_brands_by_category($categoria_url) {
    // Incluir funciones necesarias si no están disponibles
    if (!function_exists('link_marca')) {
        include_once __DIR__ . '/herramientas/links.php';
    }
    if (!function_exists('getMarcas')) {
        include_once __DIR__ . '/funciones_marca.php';
    }
    if (!function_exists('count_all_listado_codigos_array')) {
        include_once __DIR__ . '/funciones.php';
    }

    // Normalizar el slug de categoría para que funciones que esperan 'categoria_clave'
    // acepten URLs con sufijo '-comparte-y-gana'
    $slug_categoria = preg_replace('/-comparte-y-gana$/', '', $categoria_url);

    // Obtener marcas reales de la base de datos usando la función existente
    $marcas_categoria = [];

    try {
        // Usar la función getMarcas existente para obtener marcas de esta categoría
        $lista_marcas = getMarcas(null, $slug_categoria, null);

        foreach($lista_marcas as $marca) {
            // Solo incluir marcas que tienen códigos activos
            $numero_codigos = $marca["numero_codigos"] ?? 0;
            if($numero_codigos > 0) {
                $marcas_categoria[] = [
                    'nombre' => $marca["nombre"] ?? '',
                    'nombre_clave' => $marca["nombre_clave"] ?? '',
                    'imagen' => $marca["imagen"] ?? '',
                    'categoria' => $marca["categoria"] ?? '',
                    'categoria_clave' => $marca["categoria_clave"] ?? '',
                    'codes' => $numero_codigos,
                    'url' => link_marca($marca["nombre_clave"] ?? '')
                ];
            }
        }

        // Ordenar por número de códigos (más populares primero)
        usort($marcas_categoria, function($a, $b) {
            $codes_a = $a["codes"] ?? 0;
            $codes_b = $b["codes"] ?? 0;
            return $codes_b - $codes_a;
        });

    } catch (Exception $e) {
        // En caso de error, devolver array vacío
        $marcas_categoria = [];
    }

    return $marcas_categoria;
}

// Sistema de gestión jerárquica de encabezados
class HeaderManager {
    private static $current_level = 0;
    private static $header_levels = [];
    private static $header_counters = [1, 1, 1, 1, 1, 1]; // H1, H2, H3, H4, H5, H6

    public static function reset() {
        self::$current_level = 0;
        self::$header_levels = [];
        self::$header_counters = [1, 1, 1, 1, 1, 1];
    }

    public static function generateHeader($level, $text, $attributes = '', $auto_increment = true) {
        // Validar nivel de encabezado
        if ($level < 1 || $level > 6) {
            return '';
        }

        // Si el nivel es menor o igual al nivel actual, ajustar la estructura
        if ($level <= self::$current_level) {
            // Resetear niveles inferiores
            for ($i = $level; $i <= 6; $i++) {
                self::$header_counters[$i-1] = 1;
            }
        }

        self::$current_level = $level;

        $tag = 'h' . $level;
        $counter = self::$header_counters[$level-1];

        if ($auto_increment) {
            self::$header_counters[$level-1]++;
        }

        $html = "<{$tag} {$attributes}>" . htmlspecialchars($text) . "</{$tag}>";

        // Registrar nivel para rastreo
        self::$header_levels[] = $level;

        return $html;
    }

    public static function getCurrentLevel() {
        return self::$current_level;
    }

    public static function getHeaderStructure() {
        return self::$header_levels;
    }
}

// Función para generar encabezado H1 principal de la página
function generatePageHeader($title, $subtitle = '', $attributes = '') {
    $html = '<header class="page-header">';
    $html .= HeaderManager::generateHeader(1, $title, $attributes);
    if ($subtitle) {
        $html .= '<p class="page-subtitle">' . htmlspecialchars($subtitle) . '</p>';
    }
    $html .= '</header>';
    return $html;
}

// Función para generar sección principal con H2
function generateMainSection($title, $content = '', $attributes = '') {
    $html = '<section class="main-section">';
    $html .= HeaderManager::generateHeader(2, $title, $attributes);
    if ($content) {
        $html .= '<div class="section-content">' . $content . '</div>';
    }
    $html .= '</section>';
    return $html;
}

// Función para generar subsección con H3
function generateSubSection($title, $content = '', $attributes = '') {
    $html = '<div class="subsection">';
    $html .= HeaderManager::generateHeader(3, $title, $attributes);
    if ($content) {
        $html .= '<div class="subsection-content">' . $content . '</div>';
    }
    $html .= '</div>';
    return $html;
}

// Función para generar sub-subsección con H4
function generateSubSubSection($title, $content = '', $attributes = '') {
    $html = '<div class="sub-subsection">';
    $html .= HeaderManager::generateHeader(4, $title, $attributes);
    if ($content) {
        $html .= '<div class="sub-subsection-content">' . $content . '</div>';
    }
    $html .= '</div>';
    return $html;
}

// Función para generar preguntas frecuentes con estructura correcta
function generateFAQSection($title = 'Preguntas Frecuentes', $faqs = []) {
    $html = '<section class="faq-section">';
    $html .= HeaderManager::generateHeader(2, $title);

    if (!empty($faqs)) {
        $html .= '<div class="faq-list">';
        foreach ($faqs as $index => $faq) {
            $question = $faq['question'] ?? '';
            $answer = $faq['answer'] ?? '';

            $html .= '<div class="faq-item">';
            $html .= HeaderManager::generateHeader(3, $question, 'class="faq-question"', false);
            $html .= '<div class="faq-answer">' . $answer . '</div>';
            $html .= '</div>';
        }
        $html .= '</div>';
    }

    $html .= '</section>';
    return $html;
}

// Función para generar navegación de página con estructura correcta
function generatePageNavigation($sections) {
    $html = '<nav class="page-navigation">';
    $html .= HeaderManager::generateHeader(2, 'Contenido de la página');

    $html .= '<ul class="nav-list">';
    foreach ($sections as $section) {
        $title = $section['title'] ?? '';
        $url = $section['url'] ?? '#';
        $level = $section['level'] ?? 3;

        $html .= '<li class="nav-item">';
        $html .= HeaderManager::generateHeader($level, $title, 'class="nav-link"', false);
        $html .= '</li>';
    }
    $html .= '</ul>';

    $html .= '</nav>';
    return $html;
}

// Función para generar el layout de la página de categoría
function generate_category_page_layout($categoria_url, $nombre_categoria, $descripcion_categoria, $marcas_categoria) {
    $categoria_info = get_category_info($categoria_url);
    $icono = $categoria_info['icono'];

    // Resetear el sistema de encabezados para esta página
    HeaderManager::reset();

    $html = '<div class="category-page-container">';

    // Header de la categoría usando el nuevo sistema
    $html .= '<div class="category-header">';
    $html .= '<div class="category-header-content">';
    $html .= '<div class="category-icon-large">';
    $html .= '<i class="' . $icono . '"></i>';
    $html .= '</div>';
    $html .= '<div class="category-info">';
    $html .= '<h1 class="category-title">' . htmlspecialchars($nombre_categoria) . '</h1>';
    if (!empty($descripcion_categoria)) {
        $html .= '<p class="category-description">' . htmlspecialchars($descripcion_categoria) . '</p>';
    }
    $html .= '<div class="category-stats">';
    $html .= '<span class="stat-item"><i class="fas fa-tag"></i> ' . count($marcas_categoria) . ' marcas</span>';
    $total_codigos = array_sum(array_column($marcas_categoria, 'codes'));
    $html .= '<span class="stat-item"><i class="fas fa-code"></i> ' . $total_codigos . ' códigos</span>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';

    // Breadcrumb de navegación
    $html .= '<nav class="brand-navigation" aria-label="breadcrumb">';
    $html .= '<a class="nav-link" href="/">Inicio</a>';
    $html .= '<a class="nav-link" href="/listado-categorias">Categorías</a>';
    $html .= '<span class="nav-link active">' . htmlspecialchars($nombre_categoria) . '</span>';
    $html .= '</nav>';

    // Contenido principal
    $html .= '<div class="category-content">';

    if(!empty($marcas_categoria)) {
        // Ordenación según parámetro 'orden'
        $orden = isset($_GET['orden']) ? $_GET['orden'] : 'pop';
        if ($orden === 'az') {
            usort($marcas_categoria, function($a, $b) {
                return strcasecmp($a['nombre'] ?? '', $b['nombre'] ?? '');
            });
        } elseif ($orden === 'za') {
            usort($marcas_categoria, function($a, $b) {
                return strcasecmp($b['nombre'] ?? '', $a['nombre'] ?? '');
            });
        } else {
            usort($marcas_categoria, function($a, $b) {
                $aa = intval($a['codes'] ?? 0);
                $bb = intval($b['codes'] ?? 0);
                return $bb <=> $aa; // más códigos primero
            });
            $orden = 'pop';
        }

        // Controles (tabs de orden + buscador)
        $base_url = '/' . ltrim($categoria_url, '/');
        $total_brands = count($marcas_categoria);
        $html .= '<div class="brand-tabs" style="justify-content: space-between; align-items: center; flex-wrap: wrap;">';
        $html .= '  <div class="brand-tabs" style="gap: .75rem;">';
        $html .= '    <a href="' . $base_url . '?orden=pop" class="brand-tab' . ($orden==='pop'?' active':'') . '"><i class="fas fa-fire"></i> Más códigos</a>';
        $html .= '    <a href="' . $base_url . '?orden=az" class="brand-tab' . ($orden==='az'?' active':'') . '"><i class="fas fa-sort-alpha-down"></i> A-Z</a>';
        $html .= '    <a href="' . $base_url . '?orden=za" class="brand-tab' . ($orden==='za'?' active':'') . '"><i class="fas fa-sort-alpha-up"></i> Z-A</a>';
        $html .= '  </div>';
        $html .= '  <div style="display:flex; align-items:center; gap:.75rem;">';
        $html .= '    <input type="text" id="search-brands" class="brand-search" placeholder="Buscar marca..." style="max-width:320px;">';
        $html .= '    <span id="brands-result-count" class="brand-codes-count" style="white-space:nowrap;">' . $total_brands . ' resultados</span>';
        $html .= '  </div>';
        $html .= '</div>';

        // Chips por letra con conteo
        $letter_counts = [];
        foreach (range('A','Z') as $L) { $letter_counts[$L] = 0; }
        foreach ($marcas_categoria as $m) {
            $n = strtoupper(substr((string)($m['nombre'] ?? ''), 0, 1));
            $L = preg_match('/[A-Z]/', $n) ? $n : '#';
            if (!isset($letter_counts[$L])) { $letter_counts[$L] = 0; }
            $letter_counts[$L]++;
        }
        $html .= '<div class="brand-tabs" style="flex-wrap:wrap; gap:.5rem; margin-bottom:1rem;">';
        foreach (range('A','Z') as $L) {
            $count = intval($letter_counts[$L] ?? 0);
            $disabled = $count === 0 ? ' style="opacity:.4; pointer-events:none;"' : '';
            $html .= '<a href="#" class="brand-tab letter-chip" data-letter="' . $L . '"' . $disabled . '>' . $L . ' (' . $count . ')</a>';
        }
        if (!empty($letter_counts['#'])) {
            $html .= '<a href="#" class="brand-tab letter-chip" data-letter="#"># (' . intval($letter_counts['#']) . ')</a>';
        }
        $html .= '</div>';

        // Paginación de marcas
        $per_page = 24;
        $current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $total_brands = count($marcas_categoria);
        $start_index = ($current_page - 1) * $per_page;
        $marcas_paginadas = array_slice($marcas_categoria, $start_index, $per_page);
        $pagination_html = generate_modern_pagination($total_brands, $current_page, $per_page, 'Marcas');

        // Top pagination
        $html .= $pagination_html;

        $html .= '<div class="brands-grid">';
        $html .= generateMainSection('Marcas populares en ' . htmlspecialchars($nombre_categoria), '', 'class="section-title"');
        $html .= '<div class="brands-list">';

        foreach($marcas_paginadas as $marca) {
            $nombre = htmlspecialchars($marca['nombre'] ?? '');
            $nombre_clave = htmlspecialchars($marca['nombre_clave'] ?? '');
            $imagen = htmlspecialchars($marca['imagen'] ?? '');
            $codes = intval($marca['codes'] ?? 0);
            $initial = strtoupper(substr($marca['nombre'] ?? '', 0, 1));
            if (!preg_match('/[A-Z]/', $initial)) { $initial = '#'; }

            $html .= '<a class="brand-card" data-initial="' . $initial . '" href="/de-' . $nombre_clave . '" title="Códigos descuento ' . $nombre . '">';
            $html .=   '<div class="brand-card-image">';
            if ($imagen && strtolower($imagen) !== 'sin imagen') {
                $html .=     '<img loading="lazy" src="' . $imagen . '" alt="' . $nombre . '">';
            } else {
                $inicial = $nombre ? strtoupper(substr($nombre, 0, 1)) : '?';
                $html .=     '<div class="brand-card-placeholder">' . $inicial . '</div>';
            }
            $html .=   '</div>';
            $html .=   '<div class="brand-card-info">';
            $html .=     '<div class="brand-card-name">' . $nombre . '</div>';
            $html .=     '<div class="brand-card-stats">';
            $html .=       '<span class="brand-codes-count">' . $codes . ' códigos</span>';
            $html .=     '</div>';
            $html .=     '<span class="brand-card-button">Ver códigos <i class="fas fa-arrow-right"></i></span>';
            $html .=   '</div>';
            $html .= '</a>';
        }

        $html .= '</div>';
        $html .= '</div>';

        // Bottom pagination
        $html .= $pagination_html;

        // JSON-LD ItemList para SEO de lista de marcas (solo página actual)
        $item_list = [];
        $pos = ($current_page - 1) * $per_page;
        foreach ($marcas_paginadas as $m) {
            $pos++;
            $item_list[] = [
                '@type' => 'ListItem',
                'position' => $pos,
                'url' => 'https://www.codigoamigo.com/de-' . ($m['nombre_clave'] ?? ''),
                'name' => ($m['nombre'] ?? '')
            ];
        }
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'itemListElement' => $item_list
        ];
        $html .= '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';

        // Filtrado por texto (cliente) + contador + chips por letra + CTA sin resultados
        $html .= '<script>(function(){\n'
               . 'var input=document.getElementById("search-brands");\n'
               . 'var chips=document.querySelectorAll(".letter-chip");\n'
               . 'var countEl=document.getElementById("brands-result-count");\n'
               . 'var cta=document.getElementById("no-results-cta");\n'
               . 'function updateCount(){var visible=0;document.querySelectorAll(".brands-list .brand-card").forEach(function(el){if(el.style.display!=="none") visible++;});if(countEl) countEl.textContent=visible+" resultados";if(cta) cta.style.display=visible===0?"block":"none";}\n'
               . 'if(input){input.addEventListener("input",function(){var q=this.value.toLowerCase();document.querySelectorAll(".brands-list .brand-card").forEach(function(el){var n=(el.querySelector(".brand-card-name")||{}).textContent||"";el.style.display=n.toLowerCase().indexOf(q)>-1?"":"none";});updateCount();});}\n'
               . 'chips.forEach(function(chip){chip.addEventListener("click",function(e){e.preventDefault();var L=this.getAttribute("data-letter");var t=document.querySelector(".brands-list .brand-card[data-initial=\\""+L+"\\"]");if(t){t.scrollIntoView({behavior:"smooth",block:"start"});}});});\n'
               . 'updateCount();\n'
               . '})();</script>';
        
        // CTA para búsquedas sin resultados (oculto por defecto)
        $html .= '<div id="no-results-cta" class="no-brands" style="display:none;">'
               . '<h3>No hay marcas que coincidan</h3>'
               . '<p>¿Tienes un código para compartir? ¡Sé el primero en publicar uno!</p>'
               . '<a href="/nuevo_codigo" class="btn btn-primary">Publicar Código</a>'
               . '</div>';
    } else {
        $html .= '<div class="no-brands">';
        $html .= generateMainSection('Aún no hay marcas en esta categoría');
        $html .= '<p>¿Tienes un código para compartir? ¡Sé el primero en publicar uno!</p>';
        $html .= '<a href="/nuevo_codigo" class="btn btn-primary">Publicar Código</a>';
        $html .= '</div>';
    }

    $html .= '</div>';
    $html .= '</div>';

    return $html;
}

// Función para verificar la estructura de encabezados generada
function debugHeaderStructure() {
    $structure = HeaderManager::getHeaderStructure();
    $html = '<div style="position: fixed; bottom: 10px; left: 10px; background: rgba(0,0,0,0.8); color: white; padding: 10px; border-radius: 5px; font-size: 12px; z-index: 9999; max-width: 300px;">';
    $html .= '<strong>Estructura de Encabezados:</strong><br>';
    $html .= implode(' → ', $structure);
    $html .= '</div>';
    return $html;
}

// Función para generar paginación estilo Google
function generate_modern_pagination($total_items, $current_page = 1, $items_per_page = 20, $label = 'Códigos Amigo') {
    $total_pages = ceil($total_items / $items_per_page);
    
    if($total_pages <= 1) {
        return '';
    }
    
    $html = '<div class="pagination-modern pagination-google-style">';
    $html .= '<div class="pagination-info">';
    $html .= 'Mostrando del ' . (($current_page - 1) * $items_per_page + 1) . ' al ' . min($current_page * $items_per_page, $total_items) . ' de un total de <strong>' . $total_items . ' ' . htmlspecialchars($label) . '</strong>';
    $html .= '</div>';
    
    $html .= '<div class="pagination-controls">';
    
    // Construir query preservando parámetros existentes
    $query_params = $_GET ?? [];
    unset($query_params['page']);
    $buildUrl = function($page) use ($query_params) {
        $params = $query_params;
        $params['page'] = $page;
        $qs = http_build_query($params);
        return '?' . $qs;
    };
    
    // Botón anterior
    if($current_page > 1) {
        $prev_page = $current_page - 1;
        $html .= '<a href="' . $buildUrl($prev_page) . '" class="pagination-btn pagination-prev"><span class="pagination-arrow">‹</span> Anterior</a>';
    } else {
        $html .= '<span class="pagination-btn pagination-prev disabled"><span class="pagination-arrow">‹</span> Anterior</span>';
    }
    
    // Algoritmo estilo Google para mostrar páginas
    $pages_to_show = [];
    
    if($total_pages <= 7) {
        // Si hay 7 páginas o menos, mostrar todas
        for($i = 1; $i <= $total_pages; $i++) {
            $pages_to_show[] = $i;
        }
    } else {
        // Siempre mostrar primera página
        $pages_to_show[] = 1;
        
        // Calcular páginas alrededor de la actual
        $delta = 2; // Páginas a cada lado de la actual
        $start = max(2, $current_page - $delta);
        $end = min($total_pages - 1, $current_page + $delta);
        
        // Si hay gap después de la primera página, agregar puntos suspensivos
        if($start > 3) {
            $pages_to_show[] = 'ellipsis_start';
        } else {
            // Si no hay gap, mostrar páginas 2 y 3
            for($i = 2; $i < min(4, $start); $i++) {
                $pages_to_show[] = $i;
            }
        }
        
        // Agregar páginas alrededor de la actual
        for($i = $start; $i <= $end; $i++) {
            if($i != 1 && $i != $total_pages) {
                $pages_to_show[] = $i;
            }
        }
        
        // Si hay gap antes de la última página, agregar puntos suspensivos
        if($end < $total_pages - 2) {
            $pages_to_show[] = 'ellipsis_end';
        } else {
            // Si no hay gap, mostrar páginas antes de la última
            for($i = max($end + 1, $total_pages - 1); $i < $total_pages; $i++) {
                $pages_to_show[] = $i;
            }
        }
        
        // Siempre mostrar última página
        $pages_to_show[] = $total_pages;
    }
    
    // Renderizar páginas
    foreach($pages_to_show as $page) {
        if($page === 'ellipsis_start' || $page === 'ellipsis_end') {
            $html .= '<span class="pagination-ellipsis">...</span>';
        } else {
            $active_class = ($page == $current_page) ? ' active' : '';
            $html .= '<a href="' . $buildUrl($page) . '" class="pagination-btn pagination-number' . $active_class . '">' . $page . '</a>';
        }
    }
    
    // Botón siguiente
    if($current_page < $total_pages) {
        $next_page = $current_page + 1;
        $html .= '<a href="' . $buildUrl($next_page) . '" class="pagination-btn pagination-next">Siguiente <span class="pagination-arrow">›</span></a>';
    } else {
        $html .= '<span class="pagination-btn pagination-next disabled">Siguiente <span class="pagination-arrow">›</span></span>';
    }
    
    $html .= '</div>';
    $html .= '</div>';
    
    return $html;
}

// Función para generar el slider de marcas destacadas con información detallada (formato blog)
function generate_featured_brands_slider($limit = 6) {
    // Obtener marcas destacadas
    $marcas = get_featured_brands_for_home($limit);
    
    // Debug temporal - remover después
    if (isset($_GET['debug'])) {
        error_log("DEBUG generate_featured_brands_slider: " . count($marcas) . " marcas encontradas");
    }
    
    if(empty($marcas)) {
        // Si no hay marcas, intentar obtener marcas populares directamente
        if (!function_exists('getCollectionMarcas')) {
            include_once __DIR__ . '/funciones_marca.php';
        }
        if (!function_exists('getCollectionCodigos')) {
            include_once __DIR__ . '/funciones_codigo.php';
        }
        
        $collection_marcas = getCollectionMarcas();
        $collection_codigos = getCollectionCodigos();
        
        // Obtener marcas con más códigos
        $pipeline = [
            ['$match' => ['estado' => 1]],
            ['$lookup' => [
                'from' => 'codigos',
                'localField' => 'nombre_clave',
                'foreignField' => 'marca',
                'as' => 'codigos_relacionados'
            ]],
            ['$addFields' => [
                'numero_codigos' => ['$size' => '$codigos_relacionados']
            ]],
            ['$match' => [
                'numero_codigos' => ['$gt' => 0]
            ]],
            ['$sort' => ['numero_codigos' => -1]],
            ['$limit' => $limit],
            ['$project' => [
                'codigos_relacionados' => 0
            ]]
        ];
        
        $marcas_result = $collection_marcas->aggregate($pipeline)->toArray();
        
        // Asegurar que process_marca_imagen esté disponible
        if (!function_exists('process_marca_imagen')) {
            // La función está en este mismo archivo, debería estar disponible
        }
        if (!function_exists('generate_brand_advantages_with_ai')) {
            // También debería estar en este archivo
        }
        
        foreach ($marcas_result as $marca) {
            $marca_array = iterator_to_array($marca);
            $ventajas = generate_brand_advantages_with_ai($marca_array);
            
            $marcas[] = [
                'nombre' => $marca_array['nombre'] ?? '',
                'nombre_clave' => $marca_array['nombre_clave'] ?? '',
                'imagen' => process_marca_imagen($marca_array['imagen'] ?? ''),
                'categoria' => $marca_array['categoria'] ?? 'General',
                'numero_codigos' => $marca_array['numero_codigos'] ?? 0,
                'ventajas_principales' => array_slice($ventajas, 0, 3)
            ];
        }
    }
    
    if(empty($marcas)) {
        return '';
    }
    
    static $featured_brands_slider_counter = 0;
    $slider_id = 'featuredBrandsSlider-' . (++$featured_brands_slider_counter);
    
    $html = '<div class="featured-brands-blog-section">';
    $html .= '<div class="container">';
    $html .= '<div class="section-header">';
    $html .= '<div class="section-title h2-style">Marcas Destacadas</div>';
    $html .= '<p class="section-subtitle">Descubre las mejores marcas y sus principales ventajas</p>';
    $html .= '</div>';
    
    // Contenedor del slider
    $html .= '<div class="featured-brands-blog-slider-container" data-slider-root data-slider-id="' . $slider_id . '" data-slides-desktop="3" data-slides-tablet="2" data-slides-mobile="1">';
    $html .= '<div class="featured-brands-blog-slider" id="' . $slider_id . '" data-slider-track>';
    
    foreach($marcas as $marca) {
        // Asegurar que link_marca esté disponible
        if (!function_exists('link_marca')) {
            include_once __DIR__ . '/links.php';
        }
        if (!function_exists('process_marca_imagen')) {
            include_once __DIR__ . '/funciones_marca.php';
        }
        
        $nombre = htmlspecialchars($marca['nombre'] ?? '');
        $nombre_clave = htmlspecialchars($marca['nombre_clave'] ?? '');
        $imagen = process_marca_imagen($marca['imagen'] ?? '');
        $categoria = htmlspecialchars($marca['categoria'] ?? 'General');
        $numero_codigos = $marca['numero_codigos'] ?? 0;
        $ventajas = $marca['ventajas_principales'] ?? [];
        
        // Generar enlace a la marca
        $link_marca = link_marca($nombre_clave);
        
        $html .= '<div class="featured-brand-blog-card" data-slider-item>';
        
        // Logo de la marca
        $html .= '<div class="featured-brand-blog-logo">';
        $html .= '<a href="' . $link_marca . '" title="Ver códigos de ' . $nombre . '">';
        $html .= '<img src="' . htmlspecialchars($imagen) . '" alt="Logo de ' . $nombre . '" loading="lazy" onerror="this.src=\'https://www.codigoamigo.com/img/no_image.png\'">';
        $html .= '</a>';
        $html .= '</div>';
        
        // Contenido de la tarjeta
        $html .= '<div class="featured-brand-blog-content">';
        
        // Nombre y categoría
        $html .= '<div class="featured-brand-blog-header">';
        $html .= '<h3 class="featured-brand-blog-name">';
        $html .= '<a href="' . $link_marca . '" title="Ver códigos de ' . $nombre . '">' . $nombre . '</a>';
        $html .= '</h3>';
        if(!empty($categoria)) {
            $html .= '<span class="featured-brand-blog-category">' . $categoria . '</span>';
        }
        $html .= '</div>';
        
        // Principales ventajas con bullets
        if(!empty($ventajas)) {
            $html .= '<div class="featured-brand-blog-advantages">';
            $html .= '<ul class="advantages-list">';
            foreach($ventajas as $ventaja) {
                $html .= '<li><i class="fas fa-check-circle"></i> ' . htmlspecialchars($ventaja) . '</li>';
            }
            $html .= '</ul>';
            $html .= '</div>';
        }
        
        // Footer con número de códigos y botón
        $html .= '<div class="featured-brand-blog-footer">';
        $html .= '<span class="featured-brand-blog-codes">';
        $html .= '<i class="fas fa-tag"></i> ';
        $html .= '<strong>' . number_format($numero_codigos, 0, ',', '.') . '</strong> ';
        $html .= ($numero_codigos == 1 ? 'código' : 'códigos');
        $html .= '</span>';
        $html .= '<a href="' . $link_marca . '" class="btn-view-brand" title="Ver códigos de ' . $nombre . '">';
        $html .= 'Ver Códigos <i class="fas fa-arrow-right"></i>';
        $html .= '</a>';
        $html .= '</div>';
        
        $html .= '</div>'; // featured-brand-blog-content
        $html .= '</div>'; // featured-brand-blog-card
    }
    
    $html .= '</div>'; // featured-brands-blog-slider
    
    // Controles del slider
    $html .= '<button type="button" class="slider-btn slider-prev" data-slider-action="prev" data-slider-target="' . $slider_id . '">';
    $html .= '<i class="fas fa-chevron-left"></i>';
    $html .= '</button>';
    $html .= '<button type="button" class="slider-btn slider-next" data-slider-action="next" data-slider-target="' . $slider_id . '">';
    $html .= '<i class="fas fa-chevron-right"></i>';
    $html .= '</button>';
    
    // Botón "Ver todas"
    $html .= '<div class="view-all-brands-container">';
    $html .= '<a href="/listado_marcas.php" class="btn-view-all-brands">';
    $html .= 'Ver Todas las Marcas <i class="fas fa-arrow-right"></i>';
    $html .= '</a>';
    $html .= '</div>';
    
    $html .= '</div>'; // featured-brands-blog-slider-container
    $html .= '</div>'; // container
    $html .= '</div>'; // featured-brands-blog-section
    
    return $html;
}

// Función helper para extraer ventajas de una marca
function extract_brand_advantages($marca, $descripcion = '') {
    $ventajas = array();
    
    // Intentar extraer ventajas de la descripción larga si existe
    $texto_buscar = !empty($marca['descripción_larga']) ? $marca['descripción_larga'] : $descripcion;
    
    // Buscar frases comunes que indiquen ventajas
    $patrones_ventajas = array(
        '/descuento[s]?/i',
        '/ahorro/i',
        '/gratis/i',
        '/bonificaci[oó]n/i',
        '/beneficio[s]?/i',
        '/regalo[s]?/i',
        '/promoci[oó]n/i',
        '/oferta[s]?/i'
    );
    
    // Si encontramos palabras clave, crear ventajas genéricas basadas en la categoría
    $categoria = strtolower($marca['categoria'] ?? '');
    
    // Ventajas genéricas por categoría
    $ventajas_por_categoria = array(
        'viajes' => array(
            'Descuentos exclusivos en reservas',
            'Ofertas especiales para nuevos usuarios',
            'Ahorra en tus próximos viajes'
        ),
        'banca' => array(
            'Bonos de bienvenida',
            'Comisiones reducidas',
            'Beneficios exclusivos al registrarte'
        ),
        'compras' => array(
            'Descuentos en tus compras',
            'Envío gratis disponible',
            'Ofertas exclusivas'
        ),
        'alimentación' => array(
            'Descuentos en pedidos',
            'Ofertas de bienvenida',
            'Ahorra en tus compras'
        ),
        'apuestas' => array(
            'Bonos de bienvenida',
            'Apuestas gratis',
            'Ofertas exclusivas'
        )
    );
    
    // Buscar categoría coincidente
    foreach($ventajas_por_categoria as $cat_key => $ventajas_cat) {
        if(strpos($categoria, $cat_key) !== false) {
            $ventajas = array_slice($ventajas_cat, 0, 3);
            break;
        }
    }
    
    // Si no encontramos ventajas específicas, usar genéricas
    if(empty($ventajas)) {
        $ventajas = array(
            'Códigos verificados y actualizados',
            'Ahorra en tus compras',
            'Ofertas exclusivas disponibles'
        );
    }
    
    return $ventajas;
}

// Función para obtener marcas destacadas para la home
function get_featured_brands_for_home($limit = 6) {
    // Asegurar que las funciones de colección estén disponibles
    if (!function_exists('getCollectionMarcas')) {
        include_once __DIR__ . '/funciones_marca.php';
    }
    if (!function_exists('getCollectionCodigos')) {
        include_once __DIR__ . '/funciones_codigo.php';
    }
    
    $collection_marcas = getCollectionMarcas();
    $collection_codigos = getCollectionCodigos();
    $marcas_destacadas = [];
    
    try {
        // Buscar marcas marcadas como destacadas
        $marcas = $collection_marcas->find(
            [
                'estado' => 1,
                'destacada_home' => true
            ],
            [
                'limit' => $limit,
                'sort' => ['fecha_destacada' => -1]
            ]
        )->toArray();
        
        // Si no hay suficientes marcas destacadas, completar con las más populares (por número de códigos)
        if (count($marcas) < $limit) {
            // Obtener marcas con más códigos usando aggregation
            $pipeline = [
                ['$match' => [
                    'estado' => 1,
                    '$or' => [
                        ['destacada_home' => ['$ne' => true]],
                        ['destacada_home' => ['$exists' => false]]
                    ]
                ]],
                ['$lookup' => [
                    'from' => 'codigos',
                    'localField' => 'nombre_clave',
                    'foreignField' => 'marca',
                    'as' => 'codigos_relacionados'
                ]],
                ['$addFields' => [
                    'numero_codigos' => ['$size' => '$codigos_relacionados']
                ]],
                ['$match' => [
                    'numero_codigos' => ['$gt' => 0]
                ]],
                ['$sort' => ['numero_codigos' => -1]],
                ['$limit' => $limit - count($marcas)],
                ['$project' => [
                    'codigos_relacionados' => 0
                ]]
            ];
            
            $marcas_adicionales = $collection_marcas->aggregate($pipeline)->toArray();
            $marcas = array_merge($marcas, $marcas_adicionales);
        }
        
        // Si aún no hay marcas, obtener las primeras marcas activas
        if (empty($marcas)) {
            $marcas = $collection_marcas->find(
                ['estado' => 1],
                ['limit' => $limit, 'sort' => ['fecha_publicacion' => -1]]
            )->toArray();
        }
        
        // Procesar cada marca
        foreach ($marcas as $marca) {
            $marca_array = iterator_to_array($marca);
            
            // Calcular número de códigos si no existe
            if (!isset($marca_array['numero_codigos']) || $marca_array['numero_codigos'] == 0) {
                $numero_codigos = $collection_codigos->countDocuments([
                    'marca' => $marca_array['nombre_clave'] ?? '',
                    'estado' => 0
                ]);
                $marca_array['numero_codigos'] = $numero_codigos;
            }
            
            // Obtener ventajas principales (de BD o generar con IA)
            $ventajas_raw = $marca_array['ventajas_principales'] ?? [];
            
            // Convertir BSONArray a array PHP si es necesario
            if ($ventajas_raw instanceof MongoDB\Model\BSONArray || $ventajas_raw instanceof MongoDB\Model\BSONDocument) {
                $ventajas = iterator_to_array($ventajas_raw);
            } elseif (is_array($ventajas_raw)) {
                $ventajas = $ventajas_raw;
            } else {
                $ventajas = [];
            }
            
            // Si no hay ventajas, generarlas con IA
            if (empty($ventajas) || count($ventajas) < 3) {
                $ventajas = generate_brand_advantages_with_ai($marca_array);
                
                // Guardar las ventajas generadas en la BD solo si la marca está marcada como destacada
                if (!empty($ventajas) && isset($marca_array['destacada_home']) && $marca_array['destacada_home']) {
                    $collection_marcas->updateOne(
                        ['_id' => $marca['_id']],
                        ['$set' => ['ventajas_principales' => $ventajas]]
                    );
                }
            }
            
            // Asegurar que siempre haya 3 ventajas
            if (count($ventajas) < 3) {
                $ventajas = array_merge($ventajas, extract_brand_advantages($marca_array, ''));
                $ventajas = array_slice($ventajas, 0, 3);
            }
            
            $marcas_destacadas[] = [
                'nombre' => $marca_array['nombre'] ?? '',
                'nombre_clave' => $marca_array['nombre_clave'] ?? '',
                'imagen' => process_marca_imagen($marca_array['imagen'] ?? ''),
                'categoria' => $marca_array['categoria'] ?? 'General',
                'descripcion' => $marca_array['descripción'] ?? '',
                'numero_codigos' => $marca_array['numero_codigos'] ?? 0,
                'ventajas_principales' => array_slice($ventajas, 0, 3)
            ];
        }
    } catch (Exception $e) {
        error_log("Error obteniendo marcas destacadas: " . $e->getMessage());
    }
    
    return $marcas_destacadas;
}

// Función para generar ventajas principales con IA
function generate_brand_advantages_with_ai($marca) {
    $ventajas = [];
    
    $nombre = $marca['nombre'] ?? '';
    $categoria = $marca['categoria'] ?? 'General';
    $descripcion = $marca['descripción'] ?? $marca['descripción_larga'] ?? '';
    
    // Si hay descripción, intentar extraer ventajas inteligentemente
    if (!empty($descripcion)) {
        // Buscar frases que indiquen beneficios
        $patrones = [
            '/descuento[s]? de (\d+[%€]?)/i',
            '/ahorra[r]? (\d+[%€]?)/i',
            '/gratis/i',
            '/bonificaci[oó]n de (\d+[%€]?)/i',
            '/beneficio de (\d+[%€]?)/i',
            '/regalo de (\d+[%€]?)/i'
        ];
        
        foreach ($patrones as $patron) {
            if (preg_match($patron, $descripcion, $matches)) {
                $ventajas[] = "Ahorra " . ($matches[1] ?? '') . " en tu primera compra";
            }
        }
    }
    
    // Generar ventajas basadas en categoría y nombre
    $ventajas_por_categoria = [
        'viajes' => [
            "Descuentos exclusivos en reservas de {$nombre}",
            "Ofertas especiales para nuevos usuarios",
            "Ahorra hasta un 20% en tus próximos viajes"
        ],
        'banca' => [
            "Bonos de bienvenida al registrarte en {$nombre}",
            "Comisiones reducidas o sin comisiones",
            "Beneficios exclusivos para nuevos clientes"
        ],
        'compras' => [
            "Descuentos en todas tus compras en {$nombre}",
            "Envío gratis en pedidos superiores a 50€",
            "Ofertas exclusivas solo para nuevos usuarios"
        ],
        'alimentación' => [
            "Descuentos en tu primer pedido de {$nombre}",
            "Ofertas de bienvenida especiales",
            "Ahorra en tus compras de comida a domicilio"
        ],
        'apuestas' => [
            "Bono de bienvenida exclusivo de {$nombre}",
            "Apuestas gratis para nuevos usuarios",
            "Ofertas promocionales limitadas"
        ],
        'inteligencia artificial' => [
            "Créditos gratuitos al registrarte en {$nombre}",
            "Acceso premium con descuento",
            "Herramientas avanzadas sin coste adicional"
        ]
    ];
    
    // Buscar categoría coincidente
    $categoria_lower = strtolower($categoria);
    foreach ($ventajas_por_categoria as $cat_key => $ventajas_cat) {
        if (strpos($categoria_lower, $cat_key) !== false || 
            strpos($categoria_lower, str_replace(' ', '', $cat_key)) !== false) {
            $ventajas = array_merge($ventajas, $ventajas_cat);
            break;
        }
    }
    
    // Si no encontramos ventajas específicas, usar genéricas
    if (empty($ventajas)) {
        $ventajas = [
            "Códigos verificados y actualizados de {$nombre}",
            "Ahorra en tus compras con {$nombre}",
            "Ofertas exclusivas disponibles ahora"
        ];
    }
    
    // Limitar a 3 ventajas
    return array_slice($ventajas, 0, 3);
}

/********************************************************************
 *  FIN FUNCIONES PARA CHOLLOS (ELIMINADAS)
 *******************************************************************/

/**
 * Obtiene usuarios VIP con suscripción activa para mostrar en el footer.
 * Consulta directamente la colección de usuarios filtrando por is_vip=true.
 * @param int $limit Número de usuarios a obtener (por defecto 12)
 * @return array Array con información de usuarios (username, img, total_codigos)
 */
function get_usuarios_activos_footer($limit = 12) {
    try {
        if (!function_exists('getCollectionUsuarios')) {
            include_once __DIR__ . '/funciones_usuario.php';
        }

        $collection_usuarios = getCollectionUsuarios();
        $ahora = new MongoDB\BSON\UTCDateTime(time() * 1000);

        // Buscar usuarios VIP: is_vip=true y (sin vip_expires_at o con fecha futura)
        $filtro = [
            'is_vip' => true,
            '$or' => [
                ['vip_expires_at' => ['$exists' => false]],
                ['vip_expires_at' => null],
                ['vip_expires_at' => ['$gt' => $ahora]]
            ]
        ];

        $opciones = [
            'limit'      => $limit * 3, // traemos más para filtrar por foto
            'sort'       => ['fecha_registro' => -1],
            'projection' => [
                '_id'      => 1,
                'username' => 1,
                'img'      => 1,
                'avatar'   => 1,
                'foto'     => 1,
                'image'    => 1
            ]
        ];

        $cursor   = $collection_usuarios->find($filtro, $opciones);
        $resultado = [];
        $url_sin_foto = 'https://www.codigoamigo.com/img/utilidades/usuario_sin_foto.jpg';

        foreach ($cursor as $usuario) {
            if (count($resultado) >= $limit) {
                break;
            }

            $user_arr = iterator_to_array($usuario);
            $img = $user_arr['img'] ?? $user_arr['avatar'] ?? $user_arr['foto'] ?? $user_arr['image'] ?? '';

            // Usar foto por defecto si no tiene imagen propia
            if (empty($img) || strpos($img, 'usuario_sin_foto') !== false) {
                $img = $url_sin_foto;
            }

            $resultado[] = [
                'username'     => $user_arr['username'] ?? 'VIP',
                'img'          => $img,
                'total_codigos'=> 0,
                'id'           => (string)$user_arr['_id']
            ];
        }

        return $resultado;

    } catch (Exception $e) {
        error_log('get_usuarios_activos_footer error: ' . $e->getMessage());
        return [];
    }
}

/**
 * Agrega el modal global de perfil de usuario al footer
 * Se debe llamar en el footer para que esté disponible en toda la web
 */
function add_global_user_modal() {
    ?>
    <!-- User Profile Modal Global -->
    <div id="userProfileModal" class="user-modal-overlay">
        <div class="user-modal-content">
            <div class="user-modal-header">
                <h3><i class="far fa-user"></i> Estadísticas</h3>
                <button class="user-modal-close">&times;</button>
            </div>
            
            <div class="user-modal-body">
                <div class="user-modal-main-info text-center">
                    <div class="user-modal-avatar-large">
                        <img src="https://www.codigoamigo.com/img/utilidades/usuario_sin_foto.jpg" alt="Usuario" id="modalUserImg">
                    </div>
                    
                    <div class="user-modal-username" id="modalUserName" style="font-size: 1.5rem; font-weight: 700;">
                        Usuario <span class="user-badge-official" id="modalUserOfficial" style="display:none;"><i class="fas fa-check-circle"></i> Oficial</span>
                    </div>
                    
                    <p class="user-modal-member-since" id="modalMemberSince">Miembro desde hace tiempo</p>
                    
                    <div class="user-modal-quick-stats">
                        <span><strong id="modalQuickOffers">-</strong> ofertas</span>
                        <span class="user-stat-dot">•</span>
                        <span><strong id="modalQuickComments">-</strong> comentarios</span>
                    </div>
                    
                    <div class="user-modal-badges">
                        <div class="user-badge" title="Top Poster"><i class="fas fa-rocket"></i></div>
                        <div class="user-badge" title="Hot Deals"><i class="fas fa-fire"></i></div>
                        <div class="user-badge" title="Popular"><i class="fas fa-crown"></i></div>
                        <div class="user-badge" title="Verified"><i class="fas fa-certificate"></i></div>
                        <div class="user-badge-more">...</div>
                    </div>
                    
                    <div class="user-modal-actions">
                        <a href="#" class="user-modal-btn user-btn-profile" id="modalProfileLink">Mostrar perfil</a>
                        <a href="#" class="user-modal-btn user-btn-chat" id="modalChatLink"><i class="fas fa-comments"></i> Chat</a>
                        <button type="button" class="user-modal-btn user-btn-follow" id="modalFollowBtn" data-usuario-id="" style="display:none; grid-column: span 2;"><i class="fas fa-user-plus"></i> <span id="modalFollowText">Seguir</span></button>
                    </div>
                </div>
                
                <div class="user-modal-stats-list">
                    <h4>Estadísticas</h4>
                    <div class="user-stat-row">
                        <i class="fas fa-tag"></i>
                        <span><strong id="modalStatOffers">-</strong> ofertas y códigos</span>
                    </div>
                    <div class="user-stat-row">
                        <i class="fas fa-chart-line"></i>
                        <span><strong id="modalStatTemp">-°</strong> promedio</span>
                    </div>
                    <div class="user-stat-row">
                        <i class="far fa-comment"></i>
                        <span><strong id="modalStatComments">-</strong> comentarios</span>
                    </div>
                    <div class="user-stat-row">
                        <i class="far fa-thumbs-up"></i>
                        <span><strong id="modalStatLikes">-</strong> reacciones recibidas</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
    /* User Modal Styles - Dark Theme */
    .user-modal-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.7);
        z-index: 99999;
        align-items: center;
        justify-content: center;
        padding: 20px;
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .user-modal-overlay.active {
        display: flex;
        opacity: 1;
    }

    .user-modal-content {
        background: #1e1e1e;
        border-radius: 12px;
        width: 100%;
        max-width: 380px;
        box-shadow: 0 20px 50px rgba(0,0,0,0.5);
        overflow: hidden;
        color: #e0e0e0;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        transform: translateY(20px);
        transition: transform 0.3s ease;
        border: 1px solid #333;
    }

    .user-modal-overlay.active .user-modal-content {
        transform: translateY(0);
    }

    .user-modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px 20px;
        border-bottom: 1px solid #333;
    }

    .user-modal-header h3 {
        margin: 0;
        font-size: 1.1rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 10px;
        color: #fff;
    }

    .user-modal-close {
        background: none;
        border: none;
        color: #999;
        font-size: 1.5rem;
        cursor: pointer;
        padding: 0;
        line-height: 1;
        transition: color 0.2s;
    }

    .user-modal-close:hover {
        color: #fff;
    }

    .user-modal-body {
        padding: 24px;
    }

    .user-modal-avatar-large {
        width: 90px;
        height: 90px;
        margin: 0 auto 15px;
        background: #fff;
        border-radius: 50%;
        padding: 5px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    }

    .user-modal-avatar-large img {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        object-fit: contain;
    }

    .user-modal-username {
        font-size: 1.3rem;
        font-weight: 700;
        margin: 0 0 5px;
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        flex-wrap: wrap;
    }

    .user-badge-official {
        font-size: 0.75rem;
        background: rgba(227, 6, 19, 0.15);
        color: #E30613;
        padding: 2px 8px;
        border-radius: 4px;
        font-weight: 600;
        border: 1px solid rgba(227, 6, 19, 0.3);
    }

    .user-modal-member-since {
        color: #999;
        font-size: 0.9rem;
        margin: 0 0 10px;
    }

    .user-modal-quick-stats {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 8px;
        color: #ccc;
        font-size: 0.95rem;
        margin-bottom: 15px;
    }

    .user-modal-badges {
        display: flex;
        justify-content: center;
        gap: 8px;
        margin-bottom: 20px;
    }

    .user-badge {
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #2d2d2d;
        border-radius: 50%;
        color: #E30613;
        font-size: 0.9rem;
        border: 1px solid #444;
    }

    .user-badge-more {
        color: #777;
        font-size: 1.2rem;
        line-height: 20px;
        cursor: pointer;
    }

    .user-modal-actions {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
        margin-bottom: 25px;
    }

    .user-modal-btn {
        display: block;
        padding: 10px;
        border-radius: 20px;
        text-align: center;
        text-decoration: none;
        font-weight: 600;
        font-size: 0.95rem;
        transition: all 0.2s;
    }

    .user-btn-profile {
        background: transparent;
        color: #E30613;
        border: 1px solid #E30613;
    }

    .user-btn-profile:hover {
        background: rgba(227, 6, 19, 0.1);
    }

    .user-btn-chat {
        background: #E30613;
        color: white;
        border: 1px solid #E30613;
    }

    .user-btn-chat:hover {
        background: #C40510;
        border-color: #C40510;
    }
    
    .user-btn-follow {
        background: transparent;
        color: #fff;
        border: 1px solid rgba(255, 255, 255, 0.3);
        cursor: pointer;
    }
    
    .user-btn-follow:hover {
        background: rgba(255, 255, 255, 0.1);
    }
    
    .user-btn-follow.following {
        background: #444;
        border-color: #444;
        color: #ddd;
    }

    .user-modal-stats-list {
        border-top: 1px solid #333;
        padding-top: 20px;
    }

    .user-modal-stats-list h4 {
        margin: 0 0 15px;
        color: #999;
        font-size: 0.9rem;
        font-weight: 500;
    }

    .user-stat-row {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 12px;
        color: #ccc;
        font-size: 0.95rem;
    }

    .user-stat-row i {
        width: 20px;
        text-align: center;
        color: #777;
    }

    .user-stat-row strong {
        color: #fff;
        margin-right: 4px;
    }

    @media (max-width: 480px) {
        .user-modal-content {
            max-width: 100%;
            border-radius: 12px 12px 0 0;
            margin-top: auto;
            transform: translateY(100%);
        }
        .user-modal-overlay {
            align-items: flex-end;
            padding: 0;
        }
    }
    </style>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('userProfileModal');
        const closeBtn = document.querySelector('.user-modal-close');
        
        // Elementos del modal para rellenar
        const mImg = document.getElementById('modalUserImg');
        const mName = document.getElementById('modalUserName');
        const mOff = document.getElementById('modalUserOfficial'); // El span
        const mJoined = document.getElementById('modalMemberSince');
        const mQOffers = document.getElementById('modalQuickOffers');
        const mQComments = document.getElementById('modalQuickComments');
        const mLinkProfile = document.getElementById('modalProfileLink');
        const mLinkChat = document.getElementById('modalChatLink');
        const mStatOffers = document.getElementById('modalStatOffers');
        const mStatTemp = document.getElementById('modalStatTemp');
        const mStatComments = document.getElementById('modalStatComments');
        const mStatLikes = document.getElementById('modalStatLikes');
        const mFollowBtn = document.getElementById('modalFollowBtn');
        const mFollowText = document.getElementById('modalFollowText');

        // Función para cerrar el modal
        const closeModal = () => {
            if(!modal) return;
            modal.classList.remove('active');
            setTimeout(() => {
                modal.style.display = 'none';
                document.body.style.overflow = '';
            }, 300);
        };
        
        if(closeBtn) closeBtn.addEventListener('click', closeModal);
        if(modal) modal.addEventListener('click', function(e) {
            if (e.target === modal) closeModal();
        });

        /**
         * Carga estadísticas reales del usuario desde el endpoint AJAX
         * y actualiza el modal con los datos recibidos.
         */
        function cargarStatsUsuario(userId, username) {
            if (!userId) return;

            // Mostrar estado de carga
            [mQOffers, mQComments, mStatOffers, mStatComments, mStatLikes, mStatTemp].forEach(function(el) {
                if (el) el.textContent = '...';
            });

            fetch('/ajax/get_user_stats.php?user_id=' + encodeURIComponent(userId))
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (!data.ok) return;

                    // Ofertas
                    if (mQOffers)     mQOffers.textContent     = data.total_codigos;
                    if (mStatOffers)  mStatOffers.textContent  = data.total_codigos;

                    // Comentarios
                    if (mQComments)   mQComments.textContent   = data.total_comentarios;
                    if (mStatComments) mStatComments.textContent = data.total_comentarios;

                    // Likes
                    if (mStatLikes)   mStatLikes.textContent   = data.total_likes;

                    // Temperatura
                    if (mStatTemp)    mStatTemp.textContent    = data.promedio_temp || '-°';

                    // Fecha registro
                    if (mJoined)      mJoined.textContent      = data.fecha_registro || 'Miembro de la comunidad';

                    // Añadir badge VIP si procede
                    if (data.is_vip && mName) {
                        if (!mName.querySelector('.vip-badge-gold')) {
                            mName.innerHTML += ' <span class="vip-badge-gold" style="margin-left: 8px; font-size: 0.8rem; vertical-align: middle; display:inline-block;" title="Usuario VIP Verificado"><i class="fas fa-crown"></i> VIP</span>';
                        }
                    }
                    
                    // Actualizar botón de Seguir
                    if (mFollowBtn) {
                        mFollowBtn.style.display = 'block';
                        mFollowBtn.setAttribute('data-usuario-id', userId);
                        if (data.is_following) {
                            mFollowBtn.classList.add('following');
                            mFollowText.textContent = 'Dejar de seguir';
                            mFollowBtn.querySelector('i').className = 'fas fa-user-minus';
                        } else {
                            mFollowBtn.classList.remove('following');
                            mFollowText.textContent = 'Seguir';
                            mFollowBtn.querySelector('i').className = 'fas fa-user-plus';
                        }
                    }
                })
                .catch(function() { /* silencioso: los datos iniciales del data-* ya están */ });
        }
        
        // Manejar el botón de Seguir en el modal
        if (mFollowBtn) {
            mFollowBtn.addEventListener('click', function() {
                const usuarioId = this.getAttribute('data-usuario-id');
                if (!usuarioId) return;
                
                const isSiguiendo = this.classList.contains('following');
                const action = isSiguiendo ? 'eliminar_favorito' : 'añadir_favorito';
                
                const $icon = this.querySelector('i');
                const originalIconClass = $icon.className;
                $icon.className = 'fas fa-spinner fa-spin';
                
                $.ajax({
                    url: '/ajax_actions',
                    method: 'POST',
                    data: {
                        action: action,
                        codigo_id: usuarioId,
                        tipo: 'usuario'
                    },
                    success: (response) => {
                        if (typeof response === 'string') {
                            try { response = JSON.parse(response); } catch(e) {}
                        }
                        if (response && response.success) {
                            if (isSiguiendo) {
                                this.classList.remove('following');
                                mFollowText.textContent = 'Seguir';
                                $icon.className = 'fas fa-user-plus';
                            } else {
                                this.classList.add('following');
                                mFollowText.textContent = 'Dejar de seguir';
                                $icon.className = 'fas fa-user-minus';
                            }
                        } else {
                            // Suponemos error o no logueado
                            if (response && response.error === 'not_logged_in') {
                                window.location.href = '/login';
                            } else {
                                alert(response.message || 'Necesitas iniciar sesión para seguir usuarios.');
                            }
                            $icon.className = originalIconClass;
                        }
                    },
                    error: () => {
                        alert('Error de conexión');
                        $icon.className = originalIconClass;
                    }
                });
            });
        }

        // Event Delegation para abrir el modal - CON PROTECCIÓN CONTRA CONFLICTOS
        document.body.addEventListener('click', function(e) {
            // IMPORTANTE: No procesar si el clic es en elementos de login/registro
            // Solo salir de esta función sin interferir con otros event listeners
            if(e.target.closest('.open_modal_login') || 
               e.target.closest('.open_modal_registro') ||
               e.target.closest('#modal_login') ||
               e.target.closest('#modal_registro') ||
               e.target.closest('.btn-acceder') ||
               e.target.closest('#btn-login')) {
                // NO hacer nada, dejar que otros event listeners manejen esto
                return;
            }
            
            const trigger = e.target.closest('.user-modal-trigger');
            if(trigger) {
                // Solo prevenir el comportamiento por defecto si es un enlace o tiene data-href
                if(trigger.tagName === 'A' || trigger.hasAttribute('href') || trigger.hasAttribute('data-href')) {
                    e.preventDefault();
                }
                // NO usar stopPropagation para permitir que otros eventos funcionen
                
                // Extraer datos
                const ds = trigger.dataset;
                
                // Rellenar datos
                if(mImg) mImg.src = ds.image || 'https://www.codigoamigo.com/img/utilidades/usuario_sin_foto.jpg';
                
                // Nombre y oficialidad
                const username = ds.username || 'Usuario';
                const userId = ds.userId; // Asegúrate de que el trigger tenga data-user-id

                if(mName) {
                    let html = username;
                    if(ds.vip === 'true') {
                         html += ' <span class="vip-badge-gold" style="margin-left: 8px; font-size: 0.8rem; vertical-align: middle; display:inline-block;" title="Usuario VIP Verificado"><i class="fas fa-crown"></i> VIP</span>';
                    }
                    if(ds.official === 'true') {
                         html += ' <span class="user-badge-official" style="display:inline-block; font-size: 0.8rem; margin-left: 5px;"><i class="fas fa-check-circle"></i> Oficial</span>';
                    }
                    mName.innerHTML = html;
                }
                
                if(mJoined) mJoined.textContent = ds.joined || 'Miembro de la comunidad';
                
                // Stats iniciales desde data-* (pueden ser 0 si viene del footer VIP)
                const offers = ds.statsOffers || '-'; 
                const comments = ds.statsComments || '-';
                
                if(mQOffers) mQOffers.textContent = offers;
                if(mQComments) mQComments.textContent = comments;
                
                // Links
                if(mLinkProfile) {
                    // Fallback to JS generated URL if data-profile-url is missing
                    let defaultProfileUrl = '';
                    if (ds.username && ds.userId) {
                        const encodedName = encodeURIComponent(ds.username).toLowerCase().replace(/%20/g, '+');
                        defaultProfileUrl = '/usuario_' + encodedName + '_' + ds.userId;
                    } else {
                        defaultProfileUrl = '/usuario/' + username;
                    }
                    
                    const profileUrl = ds.profileUrl ? ds.profileUrl : defaultProfileUrl;
                    mLinkProfile.href = profileUrl;
                    
                    // Asegurar que el click no sea interceptado por otros scripts
                    mLinkProfile.onclick = function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        window.location.href = profileUrl;
                    };
                }
                
                // Chat Logic
                if (mLinkChat) {
                    const currentId = window.currentUserId || window.currentUserIdVar || '';
                    
                    if (currentId && userId && currentId === userId) {
                        mLinkChat.style.display = 'none'; // Ocultar si es el mismo usuario
                    } else {
                        mLinkChat.style.display = 'inline-block'; 
                        
                        // Handler de click unificado
                        mLinkChat.onclick = function(e) {
                            e.preventDefault();
                            e.stopPropagation();
                            
                            // Si no está logueado, mostrar modal de login
                            if (!currentId) {
                                // Cerrar el modal de estadísticas para que no quede por encima
                                const statsModal = document.getElementById('userProfileModal');
                                if (statsModal) {
                                    statsModal.classList.remove('active');
                                    statsModal.style.display = 'none';
                                    document.body.style.overflow = '';
                                }

                                if (typeof window.showLoginModal === 'function') {
                                    const chatUrl = '/public/chat_usuario.php?open_chat=' + userId;
                                    window.showLoginModal('Para chatear con este usuario tienes que loguearte en Código Amigo.', chatUrl);
                                } else {
                                    window.location.href = '/login.php';
                                }
                                return;
                            }
                            
                            // Si está logueado y hay userId, abrir chat
                            if (userId) {
                                const defaultMsg = encodeURIComponent("hola buenas, me ayudas con el proceso y lo hacemos juntos?");
                                window.location.href = '/public/chat_usuario.php?open_chat=' + userId + '&msg=' + defaultMsg;
                            } else {
                                console.warn('Botón chat: No hay userId para ' + username);
                            }
                        };
                        
                        mLinkChat.href = '#';
                    }
                }
                
                // Full Stats (valores iniciales)
                if(mStatOffers) mStatOffers.textContent = offers;
                if(mStatTemp) mStatTemp.textContent = (ds.statsTemp || '-°');
                if(mStatComments) mStatComments.textContent = comments;
                if(mStatLikes) mStatLikes.textContent = ds.statsLikes || '-';

                // Esconder el botón de seguir temporalmente hasta que el server conteste si es que no es el id propio
                if(mFollowBtn) {
                    mFollowBtn.style.display = 'none';
                    mFollowBtn.classList.remove('following');
                    if (mFollowText) mFollowText.textContent = 'Seguir';
                    const icon = mFollowBtn.querySelector('i');
                    if (icon) icon.className = 'fas fa-user-plus';
                }

                // Mostrar
                if(modal) {
                    modal.style.display = 'flex';
                    setTimeout(() => {
                        modal.classList.add('active');
                    }, 10);
                    document.body.style.overflow = 'hidden';
                }

                // Cargar stats reales via AJAX
                cargarStatsUsuario(userId, username);
            }
        }, false); // false = fase de bubbling (después de otros handlers)
    });
    </script>
    <?php
}




// Función para mostrar botón flotante de Telegram
function add_sticky_telegram_button() {
    echo '<a href="https://t.me/cholloscodigoamigo" target="_blank" class="sticky-telegram-btn" title="Únete a nuestro canal">';
    echo '<i class="fab fa-telegram"></i>';
    echo '</a>';
    echo '<style>
    .sticky-telegram-btn {
        position: fixed;
        bottom: 20px;
        right: 20px;
        background: linear-gradient(135deg, #0088cc, #00aaff);
        color: white;
        width: 60px;
        height: 60px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 30px;
        box-shadow: 0 4px 15px rgba(0,136,204,0.4);
        z-index: 9999;
        transition: transform 0.3s;
        animation: pulseTelegram 2s infinite;
        text-decoration: none;
    }
    .sticky-telegram-btn:hover {
        transform: scale(1.1);
        color: white;
    }
    @keyframes pulseTelegram {
        0% { box-shadow: 0 0 0 0 rgba(0,136,204, 0.7); }
        70% { box-shadow: 0 0 0 15px rgba(0,136,204, 0); }
        100% { box-shadow: 0 0 0 0 rgba(0,136,204, 0); }
    }
    @media (max-width: 768px) {
        .sticky-telegram-btn {
            bottom: 80px; /* Adjust for mobile nav bar if present */
            width: 50px;
            height: 50px;
            font-size: 24px;
        }
    }
    </style>
    <script>
    (function() {
        // Añadir clase de JS listo inmediatamente para evitar saltos bruscos
        document.documentElement.classList.add("js-ready");

        document.addEventListener("DOMContentLoaded", function() {
            const observerOptions = { threshold: 0.05, rootMargin: "0px 0px 50px 0px" };
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add("is-visible");
                        observer.unobserve(entry.target);
                    }
                });
            }, observerOptions);

            const animateElements = document.querySelectorAll(".animate-on-scroll");
            animateElements.forEach(el => observer.observe(el));
            
            // Seguridad absoluta: Mostrar todo tras 1.5s si falla algo
            setTimeout(() => {
                animateElements.forEach(el => el.classList.add("is-visible"));
            }, 1500);
        });
    })();
    </script>';
}
?>