<footer class="bg-gray-900 text-gray-300 py-12 mt-16">
  <div class="container mx-auto px-6">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-10">
      <!-- Brand -->
      <div>
        <div class="flex items-center space-x-3 mb-4">
          <i class="fas fa-tools text-2xl text-blue-400"></i>
          <h3 class="text-xl font-bold text-white">AutoParts Hub</h3>
        </div>
        <p class="mb-4 text-gray-400">
          A modern, secure, and efficient online marketplace for buying and selling vehicle parts.
        </p>
        <div class="flex space-x-4">
          <a href="#" class="text-gray-400 hover:text-white"><i class="fab fa-facebook"></i></a>
          <a href="#" class="text-gray-400 hover:text-white"><i class="fab fa-twitter"></i></a>
          <a href="#" class="text-gray-400 hover:text-white"><i class="fab fa-instagram"></i></a>
          <a href="#" class="text-gray-400 hover:text-white"><i class="fab fa-linkedin"></i></a>
        </div>
      </div>

      <!-- Quick Links -->
      <div>
        <h4 class="text-lg font-semibold text-white mb-4">Quick Links</h4>
        <ul class="space-y-2">
          <li><a href="#home" class="hover:text-white">Home</a></li>
          <li><a href="#features" class="hover:text-white">Features</a></li>
          <li><a href="#how-it-works" class="hover:text-white">How It Works</a></li>
          <li><a href="#" onclick="checkLogin('../buyer/dashboard.php'); return false;" class="hover:text-white">Browse Parts</a></li>
          <li><a href="../register.php" class="hover:text-white">Register</a></li>
        </ul>
      </div>

      <!-- Contact -->
      <div>
        <h4 class="text-lg font-semibold text-white mb-4">Contact Us</h4>
        <ul class="space-y-2 text-sm">
          <li class="flex items-start space-x-2">
            <i class="fas fa-envelope mt-1"></i>
            <span>support@autopartshub.com</span>
          </li>
          <li class="flex items-start space-x-2">
            <i class="fas fa-phone mt-1"></i>
            <span>+1 (555) 123-4567</span>
          </li>
          <li class="flex items-start space-x-2">
            <i class="fas fa-map-marker-alt mt-1"></i>
            <span>123 Auto Lane, Tech City, TC 10101</span>
          </li>
        </ul>
      </div>
    </div>

    <hr class="border-gray-800 my-8" />
    <p class="text-center text-sm text-gray-500">
      &copy; <?= date("Y") ?> AutoParts Hub. All rights reserved. | 
      <a href="#" class="hover:underline">Privacy Policy</a> | 
      <a href="#" class="hover:underline">Terms of Service</a>
    </p>
  </div>
</footer>