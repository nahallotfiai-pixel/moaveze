<?php
/**
 * Multi-Step Submission Form
 * Wizard-style form for submitting exchange listings
 */

if (!defined('ABSPATH')) {
    exit;
}

class Moaveze_Submission_Form {

    public function __construct() {
        add_shortcode('moaveze_submit_form', array($this, 'render_form'));
        add_action('wp_ajax_moaveze_submit_exchange', array($this, 'handle_submission'));
        add_action('wp_ajax_nopriv_moaveze_submit_exchange', array($this, 'handle_submission'));
        add_action('wp_ajax_moaveze_upload_image', array($this, 'handle_image_upload'));
        add_action('wp_ajax_nopriv_moaveze_upload_image', array($this, 'handle_image_upload'));
    }

    /**
     * Render the multi-step form
     */
    public function render_form($atts) {
        ob_start();
        ?>
        <div class="moaveze-wrapper">
            <div class="moaveze-form-container">
                <!-- Form Header / Progress -->
                <div class="moaveze-form-header">
                    <h2 class="moaveze-form-title">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                            <polyline points="9 22 9 12 15 12 15 22"></polyline>
                        </svg>
                        ثبت آگهی معاوضه
                    </h2>
                    <div class="moaveze-progress">
                        <div class="moaveze-progress-bar">
                            <div class="moaveze-progress-fill" id="progress-fill"></div>
                        </div>
                        <div class="moaveze-steps">
                            <div class="moaveze-step active" data-step="1">
                                <div class="step-circle">
                                    <span class="step-number">۱</span>
                                    <svg class="step-check" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                        <polyline points="20 6 9 17 4 12"></polyline>
                                    </svg>
                                </div>
                                <span class="step-label">مشخصات ملک</span>
                            </div>
                            <div class="moaveze-step" data-step="2">
                                <div class="step-circle">
                                    <span class="step-number">۲</span>
                                    <svg class="step-check" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                        <polyline points="20 6 9 17 4 12"></polyline>
                                    </svg>
                                </div>
                                <span class="step-label">تصاویر</span>
                            </div>
                            <div class="moaveze-step" data-step="3">
                                <div class="step-circle">
                                    <span class="step-number">۳</span>
                                    <svg class="step-check" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                        <polyline points="20 6 9 17 4 12"></polyline>
                                    </svg>
                                </div>
                                <span class="step-label">موقعیت</span>
                            </div>
                            <div class="moaveze-step" data-step="4">
                                <div class="step-circle">
                                    <span class="step-number">۴</span>
                                    <svg class="step-check" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                        <polyline points="20 6 9 17 4 12"></polyline>
                                    </svg>
                                </div>
                                <span class="step-label">شرایط معاوضه</span>
                            </div>
                            <div class="moaveze-step" data-step="5">
                                <div class="step-circle">
                                    <span class="step-number">۵</span>
                                    <svg class="step-check" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                        <polyline points="20 6 9 17 4 12"></polyline>
                                    </svg>
                                </div>
                                <span class="step-label">اطلاعات تماس</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form Body -->
                <form id="moaveze-exchange-form" class="moaveze-form" enctype="multipart/form-data">
                    <?php wp_nonce_field('moaveze_submit_exchange', 'moaveze_nonce'); ?>

                    <!-- Step 1: Property Details -->
                    <div class="moaveze-form-step active" data-step="1">
                        <div class="step-content">
                            <h3 class="step-title">مشخصات ملک شما</h3>
                            <p class="step-description">لطفاً اطلاعات ملکی که می‌خواهید معاوضه کنید را وارد نمایید.</p>

                            <div class="moaveze-field-group">
                                <div class="moaveze-field moaveze-field-full">
                                    <label for="property_title">عنوان آگهی <span class="required">*</span></label>
                                    <input type="text" id="property_title" name="property_title" required
                                           placeholder="مثال: آپارتمان ۱۵۰ متری در ولیعصر">
                                </div>
                            </div>

                            <div class="moaveze-field-group moaveze-field-grid-2">
                                <div class="moaveze-field">
                                    <label for="property_type">نوع ملک <span class="required">*</span></label>
                                    <select id="property_type" name="property_type" required>
                                        <option value="">انتخاب کنید...</option>
                                        <?php
                                        $types = get_terms(array('taxonomy' => 'moaveze_property_type', 'hide_empty' => false));
                                        if (!is_wp_error($types)) :
                                            foreach ($types as $type) :
                                        ?>
                                            <option value="<?php echo esc_attr($type->slug); ?>"><?php echo esc_html($type->name); ?></option>
                                        <?php endforeach; endif; ?>
                                    </select>
                                </div>
                                <div class="moaveze-field">
                                    <label for="property_district">منطقه <span class="required">*</span></label>
                                    <select id="property_district" name="property_district" required>
                                        <option value="">انتخاب کنید...</option>
                                        <?php
                                        $districts = get_terms(array('taxonomy' => 'moaveze_district', 'hide_empty' => false));
                                        if (!is_wp_error($districts)) :
                                            foreach ($districts as $district) :
                                        ?>
                                            <option value="<?php echo esc_attr($district->slug); ?>"><?php echo esc_html($district->name); ?></option>
                                        <?php endforeach; endif; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="moaveze-field-group moaveze-field-grid-3">
                                <div class="moaveze-field">
                                    <label for="property_value">ارزش ملک (تومان) <span class="required">*</span></label>
                                    <input type="text" id="property_value" name="property_value" required
                                           placeholder="مثال: 14000000000" class="moaveze-price-input">
                                    <span class="field-hint" id="value-hint"></span>
                                </div>
                                <div class="moaveze-field">
                                    <label for="area_sqm">متراژ (متر مربع) <span class="required">*</span></label>
                                    <input type="number" id="area_sqm" name="area_sqm" required min="10" max="10000"
                                           placeholder="مثال: 150">
                                </div>
                                <div class="moaveze-field">
                                    <label for="rooms">تعداد اتاق</label>
                                    <select id="rooms" name="rooms">
                                        <option value="">—</option>
                                        <?php for ($i = 1; $i <= 10; $i++) : ?>
                                            <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="moaveze-field-group moaveze-field-grid-3">
                                <div class="moaveze-field">
                                    <label for="floor">طبقه</label>
                                    <input type="number" id="floor" name="floor" min="0" max="50" placeholder="مثال: 3">
                                </div>
                                <div class="moaveze-field">
                                    <label for="total_floors">تعداد طبقات</label>
                                    <input type="number" id="total_floors" name="total_floors" min="1" max="50" placeholder="مثال: 5">
                                </div>
                                <div class="moaveze-field">
                                    <label for="year_built">سال ساخت</label>
                                    <input type="number" id="year_built" name="year_built" min="1350" max="1410" placeholder="مثال: 1400">
                                </div>
                            </div>

                            <div class="moaveze-field-group">
                                <label class="moaveze-field-label">امکانات</label>
                                <div class="moaveze-checkbox-grid">
                                    <label class="moaveze-checkbox-card">
                                        <input type="checkbox" name="features[]" value="parking">
                                        <div class="checkbox-content">
                                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
                                            <span>پارکینگ</span>
                                        </div>
                                    </label>
                                    <label class="moaveze-checkbox-card">
                                        <input type="checkbox" name="features[]" value="elevator">
                                        <div class="checkbox-content">
                                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="2"/><path d="M12 6v12M8 10l4-4 4 4M8 14l4 4 4-4"/></svg>
                                            <span>آسانسور</span>
                                        </div>
                                    </label>
                                    <label class="moaveze-checkbox-card">
                                        <input type="checkbox" name="features[]" value="storage">
                                        <div class="checkbox-content">
                                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/></svg>
                                            <span>انباری</span>
                                        </div>
                                    </label>
                                    <label class="moaveze-checkbox-card">
                                        <input type="checkbox" name="features[]" value="balcony">
                                        <div class="checkbox-content">
                                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12h18M3 12v8M21 12v8M5 12V7a7 7 0 0114 0v5"/></svg>
                                            <span>بالکن</span>
                                        </div>
                                    </label>
                                    <label class="moaveze-checkbox-card">
                                        <input type="checkbox" name="features[]" value="pool">
                                        <div class="checkbox-content">
                                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12h20M2 16c2 2 4 2 6 0s4-2 6 0 4 2 6 0"/></svg>
                                            <span>استخر</span>
                                        </div>
                                    </label>
                                    <label class="moaveze-checkbox-card">
                                        <input type="checkbox" name="features[]" value="security">
                                        <div class="checkbox-content">
                                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                                            <span>نگهبانی</span>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <div class="moaveze-field-group">
                                <div class="moaveze-field moaveze-field-full">
                                    <label for="property_description">توضیحات</label>
                                    <textarea id="property_description" name="property_description" rows="4"
                                              placeholder="توضیحات اضافی درباره ملک شما..."></textarea>
                                </div>
                            </div>
                        </div>
                    </div>


                    <!-- Step 2: Images -->
                    <div class="moaveze-form-step" data-step="2">
                        <div class="step-content">
                            <h3 class="step-title">تصاویر ملک</h3>
                            <p class="step-description">تصاویر باکیفیت شانس معاوضه موفق را افزایش می‌دهد. حداقل ۲ تصویر آپلود کنید.</p>

                            <div class="moaveze-upload-area" id="upload-area">
                                <div class="upload-placeholder" id="upload-placeholder">
                                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                        <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                        <polyline points="21 15 16 10 5 21"></polyline>
                                    </svg>
                                    <p>تصاویر را اینجا بکشید و رها کنید</p>
                                    <span>یا کلیک کنید برای انتخاب فایل</span>
                                    <small>فرمت‌های مجاز: JPG, PNG, WebP | حداکثر ۵ مگابایت</small>
                                </div>
                                <input type="file" id="property_images" name="property_images[]" multiple
                                       accept="image/jpeg,image/png,image/webp" class="upload-input">
                            </div>

                            <div class="moaveze-image-preview" id="image-preview">
                                <!-- Uploaded images will appear here -->
                            </div>
                        </div>
                    </div>

                    <!-- Step 3: Location -->
                    <div class="moaveze-form-step" data-step="3">
                        <div class="step-content">
                            <h3 class="step-title">موقعیت ملک</h3>
                            <p class="step-description">روی نقشه کلیک کنید تا موقعیت دقیق ملک مشخص شود.</p>

                            <div class="moaveze-map-container" id="submission-map">
                                <!-- Leaflet map will be rendered here -->
                            </div>

                            <div class="moaveze-field-group moaveze-field-grid-2" style="margin-top:15px;">
                                <div class="moaveze-field">
                                    <label for="latitude">عرض جغرافیایی</label>
                                    <input type="text" id="latitude" name="latitude" readonly placeholder="روی نقشه کلیک کنید">
                                </div>
                                <div class="moaveze-field">
                                    <label for="longitude">طول جغرافیایی</label>
                                    <input type="text" id="longitude" name="longitude" readonly placeholder="روی نقشه کلیک کنید">
                                </div>
                            </div>

                            <div class="moaveze-field-group">
                                <div class="moaveze-field moaveze-field-full">
                                    <label for="address">آدرس دقیق</label>
                                    <textarea id="address" name="address" rows="2"
                                              placeholder="آدرس کامل ملک را وارد کنید..."></textarea>
                                </div>
                            </div>
                        </div>
                    </div>


                    <!-- Step 4: Exchange Conditions -->
                    <div class="moaveze-form-step" data-step="4">
                        <div class="step-content">
                            <h3 class="step-title">شرایط معاوضه</h3>
                            <p class="step-description">مشخص کنید در ازای ملک خود چه می‌خواهید. می‌توانید چند شرط مختلف تعریف کنید.</p>

                            <div class="moaveze-field-group">
                                <div class="moaveze-field moaveze-field-full">
                                    <label>نوع معاوضه مورد نظر <span class="required">*</span></label>
                                    <div class="moaveze-radio-cards">
                                        <label class="moaveze-radio-card">
                                            <input type="radio" name="exchange_type" value="property_only">
                                            <div class="radio-content">
                                                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                                    <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
                                                </svg>
                                                <strong>ملک با ملک</strong>
                                                <small>فقط ملک در ازای ملک</small>
                                            </div>
                                        </label>
                                        <label class="moaveze-radio-card">
                                            <input type="radio" name="exchange_type" value="property_cash">
                                            <div class="radio-content">
                                                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                                    <line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/>
                                                </svg>
                                                <strong>ملک + نقد</strong>
                                                <small>ملک به همراه مابه‌التفاوت</small>
                                            </div>
                                        </label>
                                        <label class="moaveze-radio-card">
                                            <input type="radio" name="exchange_type" value="property_car">
                                            <div class="radio-content">
                                                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                                    <path d="M5 17h14M5 17a2 2 0 01-2-2V9l2-4h10l2 4v6a2 2 0 01-2 2M5 17a2 2 0 100 4 2 2 0 000-4zM15 17a2 2 0 100 4 2 2 0 000-4z"/>
                                                </svg>
                                                <strong>ملک + خودرو</strong>
                                                <small>ملک به همراه خودرو</small>
                                            </div>
                                        </label>
                                        <label class="moaveze-radio-card">
                                            <input type="radio" name="exchange_type" value="property_mixed">
                                            <div class="radio-content">
                                                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                                    <path d="M4 6h16M4 12h16M4 18h16"/>
                                                </svg>
                                                <strong>ترکیبی</strong>
                                                <small>ملک + نقد + دارایی دیگر</small>
                                            </div>
                                        </label>
                                        <label class="moaveze-radio-card">
                                            <input type="radio" name="exchange_type" value="flexible">
                                            <div class="radio-content">
                                                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                                    <circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2M9 9h.01M15 9h.01"/>
                                                </svg>
                                                <strong>انعطاف‌پذیر</strong>
                                                <small>پیشنهادات مختلف بررسی می‌شود</small>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Conditional fields based on exchange type -->
                            <div class="moaveze-conditional" id="cond-desired-property">
                                <div class="moaveze-field-group moaveze-field-grid-2">
                                    <div class="moaveze-field">
                                        <label for="desired_property_type">نوع ملک مورد نظر</label>
                                        <select id="desired_property_type" name="desired_property_type">
                                            <option value="">فرقی ندارد</option>
                                            <?php
                                            if (!is_wp_error($types)) :
                                                foreach ($types as $type) :
                                            ?>
                                                <option value="<?php echo esc_attr($type->slug); ?>"><?php echo esc_html($type->name); ?></option>
                                            <?php endforeach; endif; ?>
                                        </select>
                                    </div>
                                    <div class="moaveze-field">
                                        <label for="desired_districts">مناطق مورد نظر</label>
                                        <select id="desired_districts" name="desired_districts[]" multiple>
                                            <?php
                                            if (!is_wp_error($districts)) :
                                                foreach ($districts as $district) :
                                            ?>
                                                <option value="<?php echo esc_attr($district->slug); ?>"><?php echo esc_html($district->name); ?></option>
                                            <?php endforeach; endif; ?>
                                        </select>
                                        <small>چند منطقه را می‌توانید انتخاب کنید</small>
                                    </div>
                                </div>
                                <div class="moaveze-field-group moaveze-field-grid-2">
                                    <div class="moaveze-field">
                                        <label for="desired_min_value">حداقل ارزش مورد نظر (تومان)</label>
                                        <input type="text" id="desired_min_value" name="desired_min_value" class="moaveze-price-input" placeholder="مثال: 10000000000">
                                    </div>
                                    <div class="moaveze-field">
                                        <label for="desired_max_value">حداکثر ارزش مورد نظر (تومان)</label>
                                        <input type="text" id="desired_max_value" name="desired_max_value" class="moaveze-price-input" placeholder="مثال: 20000000000">
                                    </div>
                                </div>
                            </div>

                            <!-- Cash difference -->
                            <div class="moaveze-conditional" id="cond-cash">
                                <div class="moaveze-field-group moaveze-field-grid-2">
                                    <div class="moaveze-field">
                                        <label for="cash_difference">مبلغ مابه‌التفاوت (تومان)</label>
                                        <input type="text" id="cash_difference" name="cash_difference" class="moaveze-price-input" placeholder="مثال: 6000000000">
                                    </div>
                                    <div class="moaveze-field">
                                        <label for="cash_direction">جهت مابه‌التفاوت</label>
                                        <div class="moaveze-toggle-group">
                                            <label class="moaveze-toggle-btn active" data-value="give">
                                                <input type="radio" name="cash_direction" value="give" checked>
                                                <span>می‌دهم</span>
                                            </label>
                                            <label class="moaveze-toggle-btn" data-value="receive">
                                                <input type="radio" name="cash_direction" value="receive">
                                                <span>می‌گیرم</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Additional assets -->
                            <div class="moaveze-conditional" id="cond-assets">
                                <div class="moaveze-field-group">
                                    <div class="moaveze-field moaveze-field-full">
                                        <label for="additional_assets">دارایی‌های اضافی پیشنهادی</label>
                                        <textarea id="additional_assets" name="additional_assets" rows="3"
                                                  placeholder="مثال: یک دستگاه خودرو پژو پارس مدل ۱۴۰۱ به ارزش ۳ میلیارد تومان"></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- Multiple conditions (creative feature) -->
                            <div class="moaveze-multi-conditions">
                                <h4 class="moaveze-section-title">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/>
                                    </svg>
                                    شرایط جایگزین (اختیاری)
                                </h4>
                                <p class="step-description">می‌توانید چند سناریوی مختلف برای معاوضه تعریف کنید تا شانس تطابق بیشتر شود.</p>
                                <div id="alternative-conditions">
                                    <!-- Dynamic conditions will be added here -->
                                </div>
                                <button type="button" id="add-condition-btn" class="moaveze-btn moaveze-btn-outline">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                                    </svg>
                                    افزودن شرط جایگزین
                                </button>
                            </div>
                        </div>
                    </div>


                    <!-- Step 5: Contact Info -->
                    <div class="moaveze-form-step" data-step="5">
                        <div class="step-content">
                            <h3 class="step-title">اطلاعات تماس</h3>
                            <p class="step-description">اطلاعات تماس شما محرمانه بوده و فقط در اختیار مدیریت سایت قرار می‌گیرد.</p>

                            <div class="moaveze-privacy-notice">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                                </svg>
                                <div>
                                    <strong>حریم خصوصی شما محفوظ است</strong>
                                    <p>شماره تماس و اطلاعات شخصی شما به هیچ وجه به صورت عمومی نمایش داده نمی‌شود و فقط تیم مشاوران ما برای هماهنگی با شما از آن استفاده خواهند کرد.</p>
                                </div>
                            </div>

                            <div class="moaveze-field-group moaveze-field-grid-2">
                                <div class="moaveze-field">
                                    <label for="contact_name">نام و نام خانوادگی <span class="required">*</span></label>
                                    <input type="text" id="contact_name" name="contact_name" required placeholder="نام کامل شما">
                                </div>
                                <div class="moaveze-field">
                                    <label for="contact_phone">شماره تماس <span class="required">*</span></label>
                                    <input type="tel" id="contact_phone" name="contact_phone" required
                                           placeholder="۰۹۱۲۱۲۳۴۵۶۷" pattern="09[0-9]{9}" dir="ltr">
                                </div>
                            </div>

                            <div class="moaveze-field-group">
                                <div class="moaveze-field moaveze-field-full">
                                    <label for="contact_email">ایمیل (اختیاری)</label>
                                    <input type="email" id="contact_email" name="contact_email" placeholder="email@example.com" dir="ltr">
                                </div>
                            </div>

                            <!-- Terms -->
                            <div class="moaveze-field-group">
                                <label class="moaveze-terms-checkbox">
                                    <input type="checkbox" name="accept_terms" id="accept_terms" required>
                                    <span>با <a href="#" target="_blank">قوانین و مقررات</a> سایت موافقم و تأیید می‌کنم اطلاعات وارد شده صحیح است.</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Form Navigation -->
                    <div class="moaveze-form-nav">
                        <button type="button" class="moaveze-btn moaveze-btn-secondary" id="prev-step" style="display:none;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/>
                            </svg>
                            مرحله قبل
                        </button>
                        <button type="button" class="moaveze-btn moaveze-btn-primary" id="next-step">
                            مرحله بعد
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>
                            </svg>
                        </button>
                        <button type="submit" class="moaveze-btn moaveze-btn-success" id="submit-form" style="display:none;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                            ثبت آگهی معاوضه
                        </button>
                    </div>
                </form>

                <!-- Success State -->
                <div class="moaveze-success-state" id="success-state" style="display:none;">
                    <div class="success-icon">
                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                            <polyline points="22 4 12 14.01 9 11.01"/>
                        </svg>
                    </div>
                    <h3>آگهی شما با موفقیت ثبت شد!</h3>
                    <p>آگهی شما پس از بررسی توسط کارشناسان ما منتشر خواهد شد. در صورت یافتن مورد مناسب با شما تماس خواهیم گرفت.</p>
                    <a href="<?php echo get_post_type_archive_link('moaveze_exchange'); ?>" class="moaveze-btn moaveze-btn-primary">
                        مشاهده آگهی‌های معاوضه
                    </a>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }


    /**
     * Handle form submission via AJAX
     */
    public function handle_submission() {
        check_ajax_referer('moaveze_submit_exchange', 'moaveze_nonce');

        // Validate required fields
        $required = array('property_title', 'property_type', 'property_district', 'property_value', 'area_sqm', 'contact_name', 'contact_phone');
        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                wp_send_json_error(array('message' => 'لطفاً تمام فیلدهای ضروری را پر کنید.', 'field' => $field));
            }
        }

        // Sanitize data
        $title = sanitize_text_field($_POST['property_title']);
        $description = sanitize_textarea_field($_POST['property_description'] ?? '');
        $property_value = absint(str_replace(array(',', ' '), '', $_POST['property_value']));
        $area = absint($_POST['area_sqm']);

        // Determine post status
        $auto_approve = get_option('moaveze_auto_approve', 'no');
        $post_status = ($auto_approve === 'yes') ? 'publish' : 'pending';

        // Create the post
        $post_id = wp_insert_post(array(
            'post_title'   => $title,
            'post_content' => $description,
            'post_type'    => 'moaveze_exchange',
            'post_status'  => $post_status,
            'post_author'  => get_current_user_id() ?: 0,
        ));

        if (is_wp_error($post_id)) {
            wp_send_json_error(array('message' => 'خطا در ثبت آگهی. لطفاً مجدداً تلاش کنید.'));
        }

        // Set taxonomies
        wp_set_object_terms($post_id, sanitize_text_field($_POST['property_type']), 'moaveze_property_type');
        wp_set_object_terms($post_id, sanitize_text_field($_POST['property_district']), 'moaveze_district');

        if (!empty($_POST['features'])) {
            $features = array_map('sanitize_text_field', $_POST['features']);
            wp_set_object_terms($post_id, $features, 'moaveze_feature');
        }

        // Save meta fields
        $meta_fields = array(
            'property_value'        => $property_value,
            'area_sqm'              => $area,
            'rooms'                 => absint($_POST['rooms'] ?? 0),
            'floor'                 => absint($_POST['floor'] ?? 0),
            'total_floors'          => absint($_POST['total_floors'] ?? 0),
            'year_built'            => absint($_POST['year_built'] ?? 0),
            'latitude'              => sanitize_text_field($_POST['latitude'] ?? ''),
            'longitude'             => sanitize_text_field($_POST['longitude'] ?? ''),
            'address'               => sanitize_textarea_field($_POST['address'] ?? ''),
            'exchange_type'         => sanitize_text_field($_POST['exchange_type'] ?? ''),
            'desired_property_type' => sanitize_text_field($_POST['desired_property_type'] ?? ''),
            'desired_min_value'     => absint(str_replace(array(',', ' '), '', $_POST['desired_min_value'] ?? '')),
            'desired_max_value'     => absint(str_replace(array(',', ' '), '', $_POST['desired_max_value'] ?? '')),
            'cash_difference'       => absint(str_replace(array(',', ' '), '', $_POST['cash_difference'] ?? '')),
            'cash_direction'        => sanitize_text_field($_POST['cash_direction'] ?? 'give'),
            'additional_assets'     => sanitize_textarea_field($_POST['additional_assets'] ?? ''),
            'contact_name'          => sanitize_text_field($_POST['contact_name']),
            'contact_phone'         => sanitize_text_field($_POST['contact_phone']),
            'contact_email'         => sanitize_email($_POST['contact_email'] ?? ''),
        );

        foreach ($meta_fields as $key => $value) {
            update_post_meta($post_id, '_moaveze_' . $key, $value);
        }

        // Handle desired districts (multiple)
        if (!empty($_POST['desired_districts'])) {
            $desired_districts = array_map('sanitize_text_field', $_POST['desired_districts']);
            update_post_meta($post_id, '_moaveze_desired_districts', $desired_districts);
        }

        // Handle alternative conditions
        if (!empty($_POST['alt_conditions'])) {
            $alt_conditions = array_map(function($cond) {
                return array(
                    'type'        => sanitize_text_field($cond['type'] ?? ''),
                    'description' => sanitize_textarea_field($cond['description'] ?? ''),
                    'value'       => absint(str_replace(array(',', ' '), '', $cond['value'] ?? '')),
                );
            }, $_POST['alt_conditions']);
            update_post_meta($post_id, '_moaveze_alt_conditions', $alt_conditions);
        }

        // Handle uploaded images
        if (!empty($_POST['uploaded_images'])) {
            $image_ids = array_map('absint', $_POST['uploaded_images']);
            if (!empty($image_ids)) {
                set_post_thumbnail($post_id, $image_ids[0]);
                update_post_meta($post_id, '_moaveze_gallery', $image_ids);
            }
        }

        // Insert into exchanges table
        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'moaveze_exchanges',
            array(
                'post_id'               => $post_id,
                'user_id'               => get_current_user_id() ?: 0,
                'property_type'         => sanitize_text_field($_POST['property_type']),
                'property_value'        => $property_value,
                'area_sqm'              => $area,
                'rooms'                 => absint($_POST['rooms'] ?? 0),
                'district'              => sanitize_text_field($_POST['property_district']),
                'address'               => sanitize_textarea_field($_POST['address'] ?? ''),
                'latitude'              => sanitize_text_field($_POST['latitude'] ?? ''),
                'longitude'             => sanitize_text_field($_POST['longitude'] ?? ''),
                'exchange_type'         => sanitize_text_field($_POST['exchange_type'] ?? ''),
                'exchange_conditions'   => wp_json_encode($meta_fields),
                'desired_property_type' => sanitize_text_field($_POST['desired_property_type'] ?? ''),
                'desired_min_value'     => absint(str_replace(array(',', ' '), '', $_POST['desired_min_value'] ?? '')),
                'desired_max_value'     => absint(str_replace(array(',', ' '), '', $_POST['desired_max_value'] ?? '')),
                'cash_difference'       => absint(str_replace(array(',', ' '), '', $_POST['cash_difference'] ?? '')),
                'cash_direction'        => sanitize_text_field($_POST['cash_direction'] ?? 'give'),
                'additional_assets'     => sanitize_textarea_field($_POST['additional_assets'] ?? ''),
                'status'                => $post_status === 'publish' ? 'active' : 'pending',
                'contact_name'          => sanitize_text_field($_POST['contact_name']),
                'contact_phone'         => sanitize_text_field($_POST['contact_phone']),
                'contact_email'         => sanitize_email($_POST['contact_email'] ?? ''),
            ),
            array('%d','%d','%s','%d','%d','%d','%s','%s','%s','%s','%s','%s','%s','%d','%d','%d','%s','%s','%s','%s','%s','%s')
        );

        wp_send_json_success(array(
            'message' => 'آگهی شما با موفقیت ثبت شد!',
            'post_id' => $post_id,
        ));
    }

    /**
     * Handle image upload via AJAX
     */
    public function handle_image_upload() {
        check_ajax_referer('moaveze_plus_nonce', 'nonce');

        if (empty($_FILES['file'])) {
            wp_send_json_error(array('message' => 'فایلی انتخاب نشده'));
        }

        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        $file = $_FILES['file'];

        // Validate file type
        $allowed_types = array('image/jpeg', 'image/png', 'image/webp');
        if (!in_array($file['type'], $allowed_types)) {
            wp_send_json_error(array('message' => 'فرمت فایل مجاز نیست'));
        }

        // Validate file size (5MB)
        if ($file['size'] > 5 * 1024 * 1024) {
            wp_send_json_error(array('message' => 'حجم فایل بیش از ۵ مگابایت است'));
        }

        $attachment_id = media_handle_upload('file', 0);

        if (is_wp_error($attachment_id)) {
            wp_send_json_error(array('message' => 'خطا در آپلود فایل'));
        }

        $image_url = wp_get_attachment_image_url($attachment_id, 'medium');
        $thumb_url = wp_get_attachment_image_url($attachment_id, 'thumbnail');

        wp_send_json_success(array(
            'id'    => $attachment_id,
            'url'   => $image_url,
            'thumb' => $thumb_url,
        ));
    }
}

new Moaveze_Submission_Form();
