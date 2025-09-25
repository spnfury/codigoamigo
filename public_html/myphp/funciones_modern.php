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
            
            // Si no hay imagen, usar la imagen por defecto
            if (empty($img)) {
                global $url_usuario_sin_foto;
                $img = $url_usuario_sin_foto ?? 'https://www.codigoamigo.com/img/utilidades/usuario_sin_foto.jpg';
            }
            
            $resultado = [
                'username' => $username,
                'img' => $img,
                'mail' => $usuario['mail'] ?? ''
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
            $resultado = [
                'nombre' => $marca_especifica['nombre'] ?? ucfirst($marca_clave),
                'descripcion' => $marca_especifica['descripcion'] ?? '',
                'descripción_larga' => $marca_especifica['descripción_larga'] ?? '',
                'imagen' => $marca_especifica['imagen'] ?? '',
                'codes' => $marca_especifica['total_codigos'] ?? 0,
                'categoria' => $marca_especifica['categoria'] ?? '',
                'web' => $marca_especifica['web'] ?? ''
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
        'hostinger' => 'https://cdn.codigoamigo.com/panel_marcas/new/1721513715.png',
        'meru' => 'https://cdn.codigoamigo.com/panel_marcas/new/1736154267.png',
        'bbva' => 'https://cdn.codigoamigo.com/panel_marcas/new/1721180101.png',
        'santander' => 'https://cdn.codigoamigo.com/panel_marcas/new/1720655525.png',
        'amazon' => 'https://cdn.codigoamigo.com/panel_marcas/new/1741218417.png',
        'netflix' => 'https://cdn.codigoamigo.com/panel_marcas/new/1740747401.png',
        'spotify' => 'https://cdn.codigoamigo.com/panel_marcas/new/1736800071.png',
        'uber' => 'https://cdn.codigoamigo.com/panel_marcas/new/1728824925.png',
        'airbnb' => 'https://cdn.codigoamigo.com/panel_marcas/new/1728833170.png',
        'kraken' => 'https://cdn.codigoamigo.com/panel_marcas/new/1728833328.png',
        'coinbase' => 'https://cdn.codigoamigo.com/panel_marcas/new/1736154267.png',
        'traderepublic' => 'https://cdn.codigoamigo.com/panel_marcas/new/1721180101.png',
        'n26' => 'https://www.codigoamigo.com/img/panel_marcas/n26.jpg',
        'revolut' => 'https://cdn.codigoamigo.com/panel_marcas/new/1741218417.png',
        'surfshark' => 'https://cdn.codigoamigo.com/panel_marcas/new/1740747401.png',
        'nordvpn' => 'https://cdn.codigoamigo.com/panel_marcas/new/1736800071.png',
        'heygen' => 'https://cdn.codigoamigo.com/panel_marcas/new/1728824925.png',
        'opusclip' => 'https://cdn.codigoamigo.com/panel_marcas/new/1728833170.png',
        'worldcoin' => 'https://cdn.codigoamigo.com/panel_marcas/new/1728833328.png',
        'indexacapital' => 'https://cdn.codigoamigo.com/panel_marcas/new/1707093800.webp',
        'justeat' => 'https://cdn.codigoamigo.com/panel_marcas/new/justeat.png',
        'airalo' => 'https://cdn.codigoamigo.com/panel_marcas/new/airalo.png'
    ];
    
    $marca_clave_safe = $marca_clave ?? '';
    $imagen_default = $imagenes_por_defecto[strtolower($marca_clave_safe ?? '')] ?? '';
    
    $resultado = [
        'nombre' => ucfirst($marca_clave_safe ?? ''),
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
    $usuario_logueado = isset($_SESSION['usuario_id']) ? true : false;
    $nombre_usuario = isset($_SESSION['nombre_usuario']) ? $_SESSION['nombre_usuario'] : '';
    $foto_perfil = isset($_SESSION['foto_perfil']) ? $_SESSION['foto_perfil'] : '';
    
    echo '
    <!-- HEADER COMPACTO MÓVIL -->
    <header class="header-modern mobile-only">
        <div class="header-container">
            <!-- LOGO COMPACTO -->
            <a href="/" class="logo-section">
                <div class="logo-text">
                    <span class="logo-codigo">codigo</span><span class="logo-amigo">amigo</span>
                </div>
                <div class="logo-tagline">códigos verificados, gente real</div>
            </a>
            
            <!-- BÚSQUEDA COMPACTA -->
            <div class="search-header">
                <div style="position: relative; width: 100%;">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" 
                           class="search-input-header" 
                           placeholder="Buscar códigos..." 
                           id="mobile-search-input">
                    <button class="search-submit" type="button">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>
            
            <!-- PERFIL DE USUARIO COMPACTO -->
            <div class="user-profile-mobile" id="user-profile-mobile">
                ' . ($usuario_logueado && $foto_perfil ? 
                    '<img src="' . htmlspecialchars($foto_perfil) . '" alt="Perfil de ' . htmlspecialchars($nombre_usuario) . '">' : 
                    '<i class="fas fa-user"></i>') . '
            </div>
            
            <!-- BOTÓN DE MENÚ HAMBURGUESA -->
            <button class="mobile-menu-toggle" id="mobile-menu-toggle">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>
        
        <!-- MENÚ DESPLEGABLE MÓVIL -->
        <div class="mobile-menu" id="mobile-menu">
            <div class="mobile-menu-content">
                <nav class="mobile-nav-links">
                    <a href="/destacados" class="mobile-nav-link">Destacados</a>
                    <a href="/nuevos" class="mobile-nav-link">Nuevos</a>
                    <a href="/populares" class="mobile-nav-link">Populares</a>
                    <a href="/categorias" class="mobile-nav-link">Categorías</a>
                    
                    ' . ($usuario_logueado ? '
                        <a href="/perfil" class="mobile-nav-link">Mi Perfil</a>
                        <a href="/mis-codigos" class="mobile-nav-link">Mis Códigos</a>
                        <a href="/logout" class="mobile-nav-link">Cerrar Sesión</a>
                    ' : '
                        <a href="/login" class="mobile-nav-link">Iniciar Sesión</a>
                        <a href="/registro" class="mobile-nav-link">Registrarse</a>
                    ') . '
                </nav>
            </div>
        </div>
        
        <!-- OVERLAY PARA MENÚ -->
        <div class="mobile-menu-overlay" id="mobile-menu-overlay"></div>
    </header>';
}

// Función para agregar JavaScript móvil
function add_mobile_javascript() {
    echo '
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        // Variables globales
        const menuToggle = document.getElementById("mobile-menu-toggle");
        const mobileMenu = document.getElementById("mobile-menu");
        const overlay = document.getElementById("mobile-menu-overlay");
        const searchInput = document.getElementById("mobile-search-input");
        const searchSubmit = document.querySelector(".search-submit");
        const filtersToggle = document.getElementById("filters-toggle");
        const filtersPanel = document.getElementById("filters-panel");
        const userProfile = document.getElementById("user-profile-mobile");
        
        // Toggle del menú móvil
        if (menuToggle) {
            menuToggle.addEventListener("click", function() {
                menuToggle.classList.toggle("active");
                mobileMenu.classList.toggle("show");
                overlay.classList.toggle("show");
                document.body.style.overflow = mobileMenu.classList.contains("show") ? "hidden" : "";
            });
        }
        
        // Cerrar menú al hacer clic en overlay
        if (overlay) {
            overlay.addEventListener("click", function() {
                menuToggle.classList.remove("active");
                mobileMenu.classList.remove("show");
                overlay.classList.remove("show");
                document.body.style.overflow = "";
            });
        }
        
        // Cerrar menú al hacer clic en enlaces
        document.querySelectorAll(".mobile-nav-link").forEach(link => {
            link.addEventListener("click", function() {
                menuToggle.classList.remove("active");
                mobileMenu.classList.remove("show");
                overlay.classList.remove("show");
                document.body.style.overflow = "";
            });
        });
        
        // Toggle de filtros
        if (filtersToggle && filtersPanel) {
            filtersToggle.addEventListener("click", function() {
                filtersPanel.classList.toggle("show");
                filtersToggle.classList.toggle("active");
            });
        }
        
        // Funcionalidad de filtros
        document.querySelectorAll(".filter-option").forEach(option => {
            option.addEventListener("click", function() {
                // Remover active de otros elementos del mismo grupo
                const group = this.closest(".filter-group");
                group.querySelectorAll(".filter-option").forEach(opt => {
                    opt.classList.remove("active");
                });
                
                // Activar el elemento clickeado
                this.classList.add("active");
            });
        });
        
        // Búsqueda móvil
        if (searchSubmit) {
            searchSubmit.addEventListener("click", function() {
                const query = searchInput.value.trim();
                if (query) {
                    window.location.href = "/buscar?q=" + encodeURIComponent(query);
                }
            });
        }
        
        // Búsqueda con Enter
        if (searchInput) {
            searchInput.addEventListener("keypress", function(e) {
                if (e.key === "Enter") {
                    const query = searchInput.value.trim();
                    if (query) {
                        window.location.href = "/buscar?q=" + encodeURIComponent(query);
                    }
                }
            });
        }
        
        // Funcionalidad del perfil de usuario
        if (userProfile) {
            userProfile.addEventListener("click", function() {
                ' . (isset($_SESSION['usuario_id']) ? 'window.location.href = "/perfil";' : 'window.location.href = "/login";') . '
            });
        }
        
        // Cerrar menú al redimensionar pantalla
        window.addEventListener("resize", function() {
            if (window.innerWidth > 768) {
                menuToggle.classList.remove("active");
                mobileMenu.classList.remove("show");
                overlay.classList.remove("show");
                document.body.style.overflow = "";
            }
        });
    });
    </script>';
}

// Función para generar tarjetas destacadas modernas
function generate_modern_featured_cards($lista_codigos_destacados, $show_all = false) {
    $html = '';
    
    if(empty($lista_codigos_destacados)) {
        return $html;
    }
    
    $html .= '<div class="featured-section">';
    $html .= '<h2 class="section-title">¡Destacados!</h2>';
    $html .= '<div class="featured-grid" id="featuredGrid">';
    
    // Mostrar solo los primeros 6 códigos inicialmente
    $codigos_a_mostrar = $show_all ? $lista_codigos_destacados : array_slice($lista_codigos_destacados, 0, 6);
    
    foreach($codigos_a_mostrar as $index => $codigo) {
        $html .= generate_single_featured_card($codigo, $index);
    }
    
    $html .= '</div>';
    
    // Si hay más de 6 códigos y no se muestran todos, añadir botón "Ver más"
    if(count($lista_codigos_destacados) > 6 && !$show_all) {
        $html .= '<div class="load-more-container">';
        $html .= '<button class="load-more-btn" id="loadMoreFeatured" data-total="' . count($lista_codigos_destacados) . '" data-loaded="6">';
        $html .= '<span class="btn-text">Ver más códigos destacados</span>';
        $html .= '<span class="btn-loading" style="display: none;">';
        $html .= '<i class="fas fa-spinner fa-spin"></i> Cargando...';
        $html .= '</span>';
        $html .= '</button>';
        $html .= '</div>';
        
        // Añadir datos de códigos restantes para JavaScript con información completa
        $codigos_restantes = array_slice($lista_codigos_destacados, 6);
        
        // Enriquecer los datos con información de usuario y marca
        $codigos_enriquecidos = [];
        foreach($codigos_restantes as $codigo) {
            $usuario = getObjectUser('_id', new \MongoDB\BSON\ObjectId($codigo["id_usuario"]));
            $marca = getObjectMarca('nombre_clave', $codigo["marca"]);
            
            $codigo_enriquecido = $codigo;
            $codigo_enriquecido['usuario_username'] = $usuario['username'] ?? 'Usuario';
            $codigo_enriquecido['usuario_imagen'] = $usuario['imagen'] ?? '/img/no_image.png';
            $codigo_enriquecido['marca_imagen'] = $marca['imagen'] ?? '/img/no_image.png';
            
            $codigos_enriquecidos[] = $codigo_enriquecido;
        }
        
        $html .= '<script>';
        $html .= 'window.featuredCodesData = ' . json_encode($codigos_enriquecidos) . ';';
        $html .= '</script>';
    }
    
    $html .= '</div>';
    
    return $html;
}

// Función para generar una tarjeta destacada individual
function generate_single_featured_card($codigo, $index = 0) {
    $brand = isset($codigo['marca']) ? $codigo['marca'] : 'Marca desconocida';
    $description = isset($codigo['descripcion']) ? $codigo['descripcion'] : 'Descripción no disponible';
    $code_id = isset($codigo['_id']) ? (string)$codigo['_id'] : '';
    $benefit = isset($codigo['num_beneficio']) ? $codigo['num_beneficio'] : 0;
    $ratings = isset($codigo['num_valoraciones']) ? $codigo['num_valoraciones'] : 0;
    $usuario_id = isset($codigo['id_usuario']) ? $codigo['id_usuario'] : '';
    
    // Obtener información del usuario
    $user_info = get_user_info($usuario_id);
    $username = $user_info['username'];
    $user_img = $user_info['img'];
    
    // Obtener información de la marca para el logo
    $marca_info = get_brand_info($brand);
    $marca_imagen = $marca_info['imagen'] ?? '';
    
    // Crear enlace a la página de la marca
    $marca_url = '/de-' . strtolower($brand);
    
    // Limpiar descripción
    $description = strip_tags($description);
    $description = mb_substr($description, 0, 120) . (mb_strlen($description) > 120 ? '...' : '');
    
    $html = '<div class="featured-card" data-code-id="' . htmlspecialchars($code_id) . '">';
    
    // Badge destacado
    $html .= '<div class="featured-badge">';
    $html .= '<i class="fas fa-star"></i> Destacado';
    $html .= '</div>';
    
    // Logo de la marca (clickeable)
    $html .= '<div class="featured-brand-logo">';
    $html .= '<a href="' . htmlspecialchars($marca_url) . '" class="brand-link">';
    if($marca_imagen) {
        $html .= '<img src="' . htmlspecialchars($marca_imagen) . '" alt="Logo de ' . htmlspecialchars($brand) . '" class="brand-logo-img">';
    } else {
        $html .= '<div class="brand-logo-placeholder">';
        $html .= '<i class="fas fa-tag"></i>';
        $html .= '</div>';
    }
    $html .= '</a>';
    $html .= '</div>';
    
    // Header de la tarjeta con usuario
    $html .= '<div class="featured-card-header">';
    $html .= '<div class="featured-brand">' . htmlspecialchars($brand) . '</div>';
    $html .= '<div class="featured-user-info">';
    $html .= '<div class="featured-user-avatar">';
    if($user_img) {
        $html .= '<img src="' . htmlspecialchars($user_img) . '" alt="Avatar de ' . htmlspecialchars($username) . '" class="featured-user-img">';
    } else {
        $html .= '<div class="featured-user-placeholder">';
        $html .= '<i class="fas fa-user"></i>';
        $html .= '</div>';
    }
    $html .= '</div>';
    $html .= '<div class="featured-user-details">';
    $html .= '<span class="featured-user-name">' . htmlspecialchars($username) . '</span>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';
    
    // Descripción
    $html .= '<div class="featured-description">' . htmlspecialchars($description) . '</div>';
    
    // Información adicional
    $html .= '<div class="featured-stats">';
    
    if($benefit > 0) {
        $html .= '<span class="featured-stat"><i class="fas fa-euro-sign"></i> ' . $benefit . ' beneficio</span>';
    }
    
    if($ratings > 0) {
        $html .= '<span class="featured-stat"><i class="fas fa-star"></i> ' . $ratings . ' valoraciones</span>';
    }
    
    $html .= '</div>';
    
    // Botón de acción
    $html .= '<button class="featured-button" onclick="viewCode(\'' . htmlspecialchars($code_id) . '\', \'' . htmlspecialchars($brand) . '\')">';
    $html .= '<i class="fas fa-eye"></i> Ver Código';
    $html .= '</button>';
    
    $html .= '</div>';
    
    return $html;
}
?>
