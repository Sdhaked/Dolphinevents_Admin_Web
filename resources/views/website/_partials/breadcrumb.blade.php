@php
    $breadcrumb_image_path = $breadcrumb_image_path
        ? asset('storage/' . $breadcrumb_image_path)
        : 'https://images.unsplash.com/photo-1475721027785-f74eccf877e2?q=80&w=1170&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D';
    $breadcrumb_image_alt = $breadcrumb_image_alt ?? 'Breadcrumb title';
    $breadcrumb_heading_type = $breadcrumb_heading_type ?? 'h3';
    $breadcrumb_heading_text = $breadcrumb_heading_text ?? 'Breadcrumb title';
    $breadcrumb_description =
        $breadcrumb_description ??
        'Lorem ipsum dolor sit amet consectetur adipisicing elit. Iure aut, provident distinctio nobis nesciunt enim. Atque qui quasi itaque voluptatum dignissimos corrupti perspiciatis, beatae dolor vel?';
@endphp

<div class="container-fluid Breadcrumb">
    <img src="{{ $breadcrumb_image_path }}"
        alt="{{ $breadcrumb_image_alt }}" loading="lazy" decoding="async" class="crumb-img" />
    <div class="overlay-bg-color"></div>
    <div class="container">
        <div>
            <{{ $breadcrumb_heading_type }} class="hd-prim">{{ $breadcrumb_heading_text }}</{{ $breadcrumb_heading_type }}>
            <p>{{ $breadcrumb_description }}</p>
            <p><a href="#top-sec"><i class="fa-solid fa-angle-down"></i></a></p>
        </div>
    </div>
</div>
